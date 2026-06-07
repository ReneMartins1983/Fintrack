export function formatBRL(value) {
    return Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

export function formatDate(value) {
    if (!value) return '';
    // aceita 'YYYY-MM-DD' ou ISO completo
    const iso = String(value).slice(0, 10);
    const d = new Date(iso + 'T00:00:00');
    if (isNaN(d)) return '';
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

export function todayISO() {
    return new Date().toISOString().slice(0, 10);
}

export function currentMonth() {
    return new Date().toISOString().slice(0, 7);
}
