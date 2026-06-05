from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0008_equipe_logo_sponsorglobal_match_scoreboard'),
    ]

    operations = [
        migrations.AddField(
            model_name='match',
            name='overlay_type',
            field=models.CharField(blank=True, default='', max_length=10),
        ),
        migrations.AddField(
            model_name='match',
            name='overlay_equipe',
            field=models.CharField(blank=True, default='', max_length=3),
        ),
        migrations.AddField(
            model_name='match',
            name='overlay_fin',
            field=models.DateTimeField(blank=True, null=True),
        ),
    ]
