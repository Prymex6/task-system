<?php

namespace Database\Seeders;

use App\Models\Tenant\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds one row per template in config/mail_templates.php.
 *
 * Existing rows keep whatever the workspace has edited — only the label and
 * the variable whitelist are refreshed, so adding a placeholder in code does
 * not require anyone to reset their copy.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('mail_templates', []) as $name => $template) {
            $existing = EmailTemplate::where('name', $name)->first();

            if ($existing) {
                $existing->update([
                    'label' => $template['label'],
                    'variables' => $template['variables'],
                ]);

                continue;
            }

            EmailTemplate::create([
                'name' => $name,
                'label' => $template['label'],
                'subject' => $template['subject'],
                'body_html' => $template['body_html'],
                'variables' => $template['variables'],
            ]);
        }
    }
}
