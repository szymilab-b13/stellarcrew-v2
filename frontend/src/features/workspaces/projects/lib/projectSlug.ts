export const PROJECT_SLUG_REGEX = /^[a-z0-9]+(-[a-z0-9]+)*$/;

const DIACRITICS_MAP: Record<string, string> = {
  ą: 'a',
  ć: 'c',
  ę: 'e',
  ł: 'l',
  ń: 'n',
  ó: 'o',
  ś: 's',
  ź: 'z',
  ż: 'z',
};

export function toProjectSlug(value: string): string {
  return value
    .toLowerCase()
    .replace(/[ąćęłńóśźż]/g, (c) => DIACRITICS_MAP[c] ?? c)
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 120);
}
