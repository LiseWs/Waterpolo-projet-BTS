from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0011_participation_nb_cartons_jaunes'),
    ]

    operations = [
        migrations.AddField(
            model_name='match',
            name='scoreboard_team_scale',
            field=models.IntegerField(default=50, help_text='Largeur blocs équipes (15=étroit … 100=large, en fr/100)'),
        ),
    ]
