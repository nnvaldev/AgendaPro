/*
 * Formatos para Bolivia. Las fechas llegan del servidor en ISO 8601 con la
 * zona del negocio (p. ej. 2026-11-03T09:00:00-04:00): se muestran tal cual,
 * sin convertir a la zona del navegador.
 */

const moneyFormatters = new Map<string, Intl.NumberFormat>();

export function formatMoney(cents: number, currency = 'BOB'): string {
    let formatter = moneyFormatters.get(currency);

    if (!formatter) {
        formatter = new Intl.NumberFormat('es-BO', {
            style: 'currency',
            currency,
        });
        moneyFormatters.set(currency, formatter);
    }

    return formatter.format(cents / 100);
}

export function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest} min`;
    }

    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
}

/** "09:30" a partir de un ISO con zona. */
export function timeOf(iso: string): string {
    return iso.slice(11, 16);
}

/** "2026-11-03" a partir de un ISO con zona. */
export function dateOf(iso: string): string {
    return iso.slice(0, 10);
}

/** "martes, 3 de noviembre" a partir de "2026-11-03". */
export function formatDateLong(ymd: string, withYear = false): string {
    return new Intl.DateTimeFormat('es-BO', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        ...(withYear && { year: 'numeric' }),
        timeZone: 'UTC',
    }).format(new Date(`${ymd}T12:00:00Z`));
}

/** "mar 3 nov" a partir de "2026-11-03". */
export function formatDateShort(ymd: string): string {
    return new Intl.DateTimeFormat('es-BO', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        timeZone: 'UTC',
    }).format(new Date(`${ymd}T12:00:00Z`));
}

/** Suma días a "YYYY-MM-DD" sin pasar por la zona del navegador. */
export function addDays(ymd: string, days: number): string {
    const date = new Date(`${ymd}T12:00:00Z`);
    date.setUTCDate(date.getUTCDate() + days);

    return date.toISOString().slice(0, 10);
}

/** Domingo = 0, igual que Carbon y la columna working_hours.weekday. */
export const WEEKDAYS = [
    'Domingo',
    'Lunes',
    'Martes',
    'Miércoles',
    'Jueves',
    'Viernes',
    'Sábado',
] as const;

/** Orden de la semana laboral en Bolivia: lunes primero. */
export const WEEKDAY_ORDER = [1, 2, 3, 4, 5, 6, 0] as const;
