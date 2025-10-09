<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('pages')->insert([
            'slug' => 'email_faq',
            'title' => 'Email -FAQ',
            'content' => '# Häufige Fragen (FAQ)

## Muss ich auf diese Mail antworten?
Nein. Du musst hier nicht antworten.  
Das System erkennt automatisch, welche Videos du heruntergeladen hast und welche nicht.

## Wo sehe ich, welche Videos ich schon abgeholt habe?
In der Angebotsübersicht siehst du bereits heruntergeladene Videos ausgegraut.  
So hast du jederzeit den Überblick, was du schon genutzt hast.

## Was passiert, wenn ich ein Video nicht brauche?
Du musst keine Rückmeldung geben.  
Wenn du ein Video **nicht herunterlädst**, wird es beim nächsten Lauf automatisch wieder freigegeben und anderen Kanälen angeboten.  
Alternativ kannst du Videos auch aktiv in der Angebotsübersicht zurückgeben, wenn du sofort klarstellen willst, dass du sie nicht brauchst.

## Warum kann ich nachträglich nicht erneut herunterladen?
Download-Links sind zeitlich limitiert.  
Wenn du ein Video nach Ablauf noch brauchst, melde dich bitte beim Admin.',
            'section' => 'email',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $id = DB::table('config_categories')->where('slug', 'email')->value('id');
        DB::table('configs')
            ->insert([
                'key' => 'faq_email',
                'value' => 1,
                'cast_type' => 'bool',
                'config_category_id' => $id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'is_visible' => 1,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pages')->where('slug', 'email_faq')->delete();
        DB::table('configs')->where('key', 'faq_email')->delete();
    }
};
