interface LogoProps {
  size?: number;
  className?: string;
  title?: string;
}

export function Logo({ size = 32, className, title = 'StellarCrew' }: LogoProps) {
  return (
    <svg
      viewBox="0 0 100 100"
      width={size}
      height={size}
      className={className}
      role="img"
      aria-label={title}
      xmlns="http://www.w3.org/2000/svg"
    >
      <title>{title}</title>
      <circle
        cx="50"
        cy="50"
        r="34"
        fill="none"
        stroke="var(--sc-primary, #59d588)"
        strokeWidth="7"
        strokeLinecap="round"
        strokeDasharray="56 16"
        strokeDashoffset="4"
        transform="rotate(-80 50 50)"
      />
      <circle cx="21" cy="55" r="5.5" fill="var(--sc-primary, #59d588)" />
      <circle cx="75" cy="27" r="6" fill="var(--sc-primary, #59d588)" />
      <circle cx="56" cy="83" r="5.5" fill="var(--sc-primary, #59d588)" />
    </svg>
  );
}
