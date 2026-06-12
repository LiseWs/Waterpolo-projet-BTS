from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0014_match_scoreboard_offsets'),
    ]

    operations = [
        # Offset du score seul
        migrations.AddField(model_name='match', name='scoreboard_offset_score_dom_x', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_score_dom_y', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_score_ext_x', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_score_ext_y', field=models.IntegerField(default=0)),
        # Taille + offset des logos équipes
        migrations.AddField(model_name='match', name='scoreboard_logo_dom_scale',    field=models.IntegerField(default=100)),
        migrations.AddField(model_name='match', name='scoreboard_logo_ext_scale',    field=models.IntegerField(default=100)),
        migrations.AddField(model_name='match', name='scoreboard_offset_logo_dom_x', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_logo_dom_y', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_logo_ext_x', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_logo_ext_y', field=models.IntegerField(default=0)),
    ]
