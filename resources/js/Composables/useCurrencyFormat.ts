export function useCurrencyFormat(currency = 'IDR') {
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
    });

    const format = (value: string | number): string => formatter.format(Number(value));

    return { format };
}
