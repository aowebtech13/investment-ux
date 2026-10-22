<x-mail::message>
# {{ $title }}

Hello {{ $user->name }},

{{ $message }}

<x-mail::button :url="$url">
View Dashboard
</x-mail::button>

If you have any questions, feel free to reply to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
