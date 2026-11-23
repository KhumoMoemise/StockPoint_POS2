export function money(value: number | string | null | undefined): string {
    const n = Number(value ?? 0);

    return 'R ' + (Number.isFinite(n) ? n : 0).toFixed(2);
}

export function numberValue(value: number | string | null | undefined): number {
    const n = Number(value ?? 0);

    return Number.isFinite(n) ? n : 0;
}

export function dateTime(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-ZA', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

export function dateOnly(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-ZA', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso));
}

export function timeOnly(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-ZA', { hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
}
