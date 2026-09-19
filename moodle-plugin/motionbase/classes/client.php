<?php
// This file is part of the MotionBase plugin for Moodle.

namespace filter_motionbase;

use Firebase\JWT\JWT;
use mod_lti\local\ltiopenid\jwks_helper;
use moodle_exception;

/**
 * Talks to MotionBase on behalf of this site.
 *
 * No secret of its own: each request carries a short-lived token signed with
 * the key this site already uses for LTI. MotionBase knows this site as an LTI
 * platform and checks the token against the key set Moodle publishes - so if
 * MotionBase works here as an external tool, the plugin works too.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    /**
     * MotionBase's address, without a trailing slash.
     *
     * @return string
     */
    public static function base(): string {
        return rtrim(get_config('filter_motionbase', 'url') ?: 'https://motionbase.ch', '/');
    }

    /**
     * The MotionBase tool configured in this site, if there is one.
     *
     * @return \stdClass|null
     */
    public static function tool(): ?\stdClass {
        global $DB;

        $host = parse_url(self::base(), PHP_URL_HOST);
        $types = $DB->get_records('lti_types', ['ltiversion' => '1.3.0', 'state' => 1, 'course' => SITEID]);

        foreach ($types as $type) {
            if ($type->tooldomain === $host || parse_url($type->baseurl, PHP_URL_HOST) === $host) {
                return $type;
            }
        }

        return null;
    }

    /**
     * GET a path under MotionBase's /moodle endpoint.
     *
     * @param string $path e.g. "catalog"
     * @param int $timeout Seconds; short where a learner is waiting for a page
     * @return array
     * @throws moodle_exception
     */
    public static function get(string $path, int $timeout = 15): array {
        global $CFG;

        $tool = self::tool();

        if (!$tool) {
            throw new moodle_exception('error:notool', 'filter_motionbase', '', self::base());
        }

        $key = jwks_helper::get_private_key();
        $now = time();

        $token = JWT::encode([
            'iss' => $CFG->wwwroot,
            'sub' => $tool->clientid,
            'aud' => self::base() . '/moodle',
            'iat' => $now,
            'exp' => $now + 60,
            'jti' => random_string(32),
        ], $key['key'], 'RS256', $key['kid']);

        $curl = new \curl();
        $curl->setHeader(['Authorization: Bearer ' . $token, 'Accept: application/json']);
        $body = $curl->get(self::base() . '/moodle/' . $path, [], ['CURLOPT_TIMEOUT' => $timeout, 'CURLOPT_CONNECTTIMEOUT' => min($timeout, 5)]);
        $status = (int) ($curl->get_info()['http_code'] ?? 0);

        if ($curl->get_errno() || $status !== 200) {
            $reason = $status === 401 ? 'error:unknownsite' : 'error:unreachable';
            throw new moodle_exception($reason, 'filter_motionbase', '', self::base());
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new moodle_exception('error:unreachable', 'filter_motionbase', '', self::base());
        }

        return $data;
    }
}
