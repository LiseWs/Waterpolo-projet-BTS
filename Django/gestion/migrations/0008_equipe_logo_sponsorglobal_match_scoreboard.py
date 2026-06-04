from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0007_add_est_sur_terrain_and_new_action_codes'),
    ]

    operations = [
        migrations.AddField(
            model_name='equipe',
            name='logo',
            field=models.FileField(blank=True, null=True, upload_to='logos/'),
        ),
        migrations.CreateModel(
            name='SponsorGlobal',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True,
                                           serialize=False, verbose_name='ID')),
                ('image', models.FileField(upload_to='sponsors/')),
                ('nom', models.CharField(blank=True, max_length=100)),
                ('ordre', models.IntegerField(default=0)),
            ],
            options={
                'ordering': ['ordre', 'id'],
                'verbose_name': 'Sponsor global',
                'verbose_name_plural': 'Sponsors globaux',
            },
        ),
        migrations.AddField(
            model_name='match',
            name='scoreboard_score_scale',
            field=models.IntegerField(default=100,
                                      help_text='Taille du score en % (60-200)'),
        ),
        migrations.AddField(
            model_name='match',
            name='scoreboard_sponsor_height',
            field=models.IntegerField(default=10,
                                      help_text='Hauteur barre sponsors en vh (5-30)'),
        ),
        migrations.AddField(
            model_name='match',
            name='scoreboard_players_visible',
            field=models.BooleanField(default=True,
                                      help_text='Afficher la zone joueurs sur le scoreboard'),
        ),
    ]
