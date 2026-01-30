import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 2048 2048" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="logoGradBlue" x1="1481.36" y1="933.86" x2="1016.4" y2="1202.3" gradientUnits="userSpaceOnUse">
                    <stop offset=".01" stopColor="#7bcedc" />
                    <stop offset=".39" stopColor="#6590bf" />
                    <stop offset=".78" stopColor="#5157a5" />
                </linearGradient>
                <linearGradient id="logoGradPink" x1="361.25" y1="1300.08" x2="961.16" y2="953.72" gradientUnits="userSpaceOnUse">
                    <stop offset=".13" stopColor="#ce4f9c" />
                    <stop offset=".64" stopColor="#e02d71" />
                    <stop offset="1" stopColor="#ec1a58" />
                </linearGradient>
                <linearGradient id="logoGradYellow" x1="863.05" y1="1156.04" x2="1174.35" y2="1156.04" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stopColor="#fff23a" />
                    <stop offset=".04" stopColor="#fee52c" />
                    <stop offset=".12" stopColor="#fdd41a" />
                    <stop offset=".2" stopColor="#fdc90e" />
                    <stop offset=".28" stopColor="#fdc60b" />
                    <stop offset=".67" stopColor="#f28f3f" />
                    <stop offset=".89" stopColor="#ed693c" />
                    <stop offset="1" stopColor="#e83e39" />
                </linearGradient>
                <clipPath id="logoClip">
                    <rect x="0" y="0" width="2048" height="2048" rx="256" ry="256" />
                </clipPath>
            </defs>
            <g clipPath="url(#logoClip)">
                {/* Black background */}
                <rect width="2048" height="2048" fill="#000" />
                {/* Right blue/cyan triangle */}
                <path
                    fill="url(#logoGradBlue)"
                    d="M1240.61,636.88l-377.56,653.95h755.12c30.79,0,50.04-33.33,34.64-60l-342.92-593.95c-15.4-26.67-53.89-26.67-69.28,0Z"
                />
                {/* Left pink/magenta triangle */}
                <path
                    fill="url(#logoGradPink)"
                    d="M725.59,636.88l-342.92,593.95c-15.4,26.67,3.85,60,34.64,60h755.12l-377.56-653.95c-15.4-26.67-53.89-26.67-69.28,0Z"
                />
                {/* Center overlap - yellow/orange */}
                <polygon
                    fill="url(#logoGradYellow)"
                    points="1018.7 1021.25 1096.53 1156.04 1174.35 1290.84 1018.7 1290.84 863.05 1290.84 940.88 1156.04 1018.7 1021.25"
                />
            </g>
        </svg>
    );
}
