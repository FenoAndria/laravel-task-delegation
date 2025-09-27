const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
const dayFormatter = new Intl.DateTimeFormat('en', { day: 'numeric', month: 'short' });
const dayWithYearFormatter = new Intl.DateTimeFormat('en', { day: 'numeric', month: 'short', year: 'numeric' });

const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 60 * 60 * 24 * 365],
    ['month', 60 * 60 * 24 * 30],
    ['week', 60 * 60 * 24 * 7],
    ['day', 60 * 60 * 24],
    ['hour', 60 * 60],
    ['minute', 60],
];

/**
 * "3 hours ago", "yesterday", "just now".
 */
export function timeAgo(iso: string | null, now: Date = new Date()): string {
    if (!iso) return '';

    const seconds = Math.round((new Date(iso).getTime() - now.getTime()) / 1000);

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return formatter.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}

/**
 * "24 Nov", or "24 Nov 2027" outside the current year. Expects a Y-m-d date.
 */
export function shortDate(date: string | null, now: Date = new Date()): string {
    if (!date) return '';

    const value = new Date(`${date}T00:00:00`);

    return (value.getFullYear() === now.getFullYear() ? dayFormatter : dayWithYearFormatter).format(value);
}
