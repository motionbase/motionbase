import { useCallback, useEffect, useRef, useState } from 'react';

export type AutosaveStatus = 'idle' | 'unsaved' | 'saving' | 'saved' | 'error';

interface UseAutosaveOptions {
    /** Whether there are changes worth persisting. */
    isDirty: boolean;
    /** Performs the save. Reject (or throw) to surface an error state. */
    save: () => Promise<void>;
    /** Pause autosaving, e.g. while switching between sections. */
    enabled?: boolean;
    /** Idle time in ms before an automatic save fires. */
    delay?: number;
    /**
     * Anything that changes when the content changes - a counter is enough.
     *
     * Without it the timer is only ever scheduled when isDirty flips, so
     * continuous typing never restarts it and the save turns from a debounce
     * into a fixed interval.
     */
    changeKey?: unknown;
}

interface UseAutosaveResult {
    status: AutosaveStatus;
    lastSavedAt: Date | null;
    /** Saves immediately (Cmd/Ctrl+S, leaving a section, …). */
    saveNow: () => Promise<void>;
}

/**
 * Debounced autosave. Only one save is ever in flight; if the content changes
 * again while a save runs, a follow-up save is queued for afterwards so the
 * last keystroke always makes it to the server.
 */
export function useAutosave({
    isDirty,
    save,
    enabled = true,
    delay = 1500,
    changeKey,
}: UseAutosaveOptions): UseAutosaveResult {
    const [status, setStatus] = useState<AutosaveStatus>('idle');
    const [lastSavedAt, setLastSavedAt] = useState<Date | null>(null);

    const saveRef = useRef(save);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const inFlightRef = useRef<Promise<void> | null>(null);
    const rerunRef = useRef(false);

    useEffect(() => {
        saveRef.current = save;
    });

    const clearTimer = useCallback(() => {
        if (timerRef.current !== null) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    const runSave = useCallback((): Promise<void> => {
        if (inFlightRef.current) {
            // A save is already running — remember that the newest state still
            // needs to go out, and hand back the promise for the WHOLE chain,
            // not just the running attempt. Callers that flush before leaving a
            // section await this; resolving after the older attempt alone would
            // let them navigate away while the newest edits are still unsaved,
            // and the follow-up save then aborts because the section changed.
            rerunRef.current = true;

            return inFlightRef.current;
        }

        const chain = (async () => {
            try {
                do {
                    rerunRef.current = false;
                    clearTimer();
                    setStatus('saving');

                    try {
                        await saveRef.current();
                        setStatus('saved');
                        setLastSavedAt(new Date());
                    } catch {
                        // Leave the retry to the scheduling effect rather than
                        // spinning against a server that is currently failing.
                        setStatus('error');
                        break;
                    }
                } while (rerunRef.current);
            } finally {
                inFlightRef.current = null;
            }
        })();

        inFlightRef.current = chain;

        return chain;
    }, [clearTimer]);

    // Two dependencies here are less obvious than they look.
    //
    // `changeKey` restarts the timer on every edit. isDirty stays true while
    // someone keeps typing, so on its own the effect never runs again and the
    // first keystroke's timer fires mid-sentence - a save every delay rather
    // than one after the typing stops.
    //
    // `status` covers the other direction: editing *during* a save also leaves
    // isDirty at true, and without a re-run the queued edits would never get a
    // timer of their own.
    useEffect(() => {
        if (!enabled || !isDirty || status === 'saving') {
            return;
        }

        setStatus((current) => (current === 'saving' ? current : 'unsaved'));

        timerRef.current = setTimeout(() => {
            void runSave();
        }, delay);

        return clearTimer;
    }, [isDirty, enabled, delay, status, changeKey, runSave, clearTimer]);

    return {
        status,
        lastSavedAt,
        saveNow: runSave,
    };
}
