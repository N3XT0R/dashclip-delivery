const fs = require('node:fs');
for (const file of ['tokens/channel-access-approved', 'tokens/channel-activation-approved', 'channels/approved']) {
 const path=`resources/views/${file}.blade.php`;
 let view=fs.readFileSync(path,'utf8');
 view=view.replace(/    <div class="panel"[^>]+>\s*<h1[^>]+>([\s\S]*?)<\/h1>/, '    <x-token-action-panel headline="$1">');
 view=view.replace(/        <hr class="muted-separator"[\s\S]*?    <\/div>\s*@endsection/, '    </x-token-action-panel>\n@endsection');
 view=view.replace(/ style="[^"]*"/g,'');
 view=view.replace("@section('content')", "@section('robots', 'noindex, nofollow')\n@section('content')");
 fs.writeFileSync(path,view);
}
for (const file of ['tokens/channel-reception-confirm', 'tokens/channel-reception-reactivated']) {
 const path=`resources/views/${file}.blade.php`;
 let view=fs.readFileSync(path,'utf8').replace(/ style="[^"]*"/g,'');
 view=view.replace("@section('content')", "@section('robots', 'noindex, nofollow')\n@section('content')");
 fs.writeFileSync(path,view);
}
const terms='resources/views/tos.blade.php';
fs.writeFileSync(terms,fs.readFileSync(terms,'utf8').replace("@section('content')", "@section('description', 'Nutzungsbedingungen für Uploads, Angebote und Downloads bei DashClip Delivery.')\n@section('content')"));
