@php use App\Facades\Cfg; @endphp
        <!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{$this->envelope()->subject}}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, sans-serif;">
@include('emails.partials.header')
<x-page slug="email_faq"/>
@include('emails.partials.footer')
</body>
</html>