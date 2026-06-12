from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0012_match_scoreboard_team_scale'),
    ]

    operations = [
        migrations.AddField(
            model_name='sponsor',
            name='scale',
            field=models.IntegerField(default=100, help_text='Taille individuelle en % (20-300)'),
        ),
    ]
