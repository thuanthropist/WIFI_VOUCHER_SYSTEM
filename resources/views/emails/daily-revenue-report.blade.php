<x-mail::message>
# Daily Revenue Report — {{ $day }}

| Metric | Value |
|:--|--:|
| Confirmed payments | {{ $paymentsCount }} |
| Revenue | {{ number_format((float) $revenue, 0) }} |
| Vouchers issued | {{ $vouchersIssued }} |

<x-mail::button :url="config('app.url').'/admin'">
View Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
