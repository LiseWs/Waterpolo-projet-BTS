from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0015_match_score_logo_offsets'),
    ]

    operations = [
        migrations.CreateModel(
            name='ReglagesEcran',
            fields=[
                ('id', models.AutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('scoreboard_score_scale',        models.IntegerField(default=100)),
                ('scoreboard_sponsor_height',     models.IntegerField(default=10)),
                ('scoreboard_sponsor_scale',      models.IntegerField(default=100)),
                ('scoreboard_players_visible',    models.BooleanField(default=True)),
                ('scoreboard_team_scale',         models.IntegerField(default=50)),
                ('scoreboard_offset_dom_x',       models.IntegerField(default=0)),
                ('scoreboard_offset_dom_y',       models.IntegerField(default=0)),
                ('scoreboard_offset_mid_x',       models.IntegerField(default=0)),
                ('scoreboard_offset_mid_y',       models.IntegerField(default=0)),
                ('scoreboard_offset_ext_x',       models.IntegerField(default=0)),
                ('scoreboard_offset_ext_y',       models.IntegerField(default=0)),
                ('scoreboard_offset_score_dom_x', models.IntegerField(default=0)),
                ('scoreboard_offset_score_dom_y', models.IntegerField(default=0)),
                ('scoreboard_offset_score_ext_x', models.IntegerField(default=0)),
                ('scoreboard_offset_score_ext_y', models.IntegerField(default=0)),
                ('scoreboard_logo_dom_scale',     models.IntegerField(default=100)),
                ('scoreboard_logo_ext_scale',     models.IntegerField(default=100)),
                ('scoreboard_offset_logo_dom_x',  models.IntegerField(default=0)),
                ('scoreboard_offset_logo_dom_y',  models.IntegerField(default=0)),
                ('scoreboard_offset_logo_ext_x',  models.IntegerField(default=0)),
                ('scoreboard_offset_logo_ext_y',  models.IntegerField(default=0)),
                ('scoreboard_offset_name_dom_x',  models.IntegerField(default=0)),
                ('scoreboard_offset_name_dom_y',  models.IntegerField(default=0)),
                ('scoreboard_offset_name_ext_x',  models.IntegerField(default=0)),
                ('scoreboard_offset_name_ext_y',  models.IntegerField(default=0)),
            ],
            options={'verbose_name': 'Réglages écran'},
        ),
    ]
