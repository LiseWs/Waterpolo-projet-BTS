from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0010_match_scoreboard_sponsor_scale'),
    ]

    operations = [
        migrations.AddField(
            model_name='participation',
            name='nb_cartons_jaunes',
            field=models.IntegerField(default=0),
        ),
    ]
