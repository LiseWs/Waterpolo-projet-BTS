from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0013_sponsor_scale'),
    ]

    operations = [
        migrations.AddField(model_name='match', name='scoreboard_offset_dom_x', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_dom_y', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_mid_x', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_mid_y', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_ext_x', field=models.IntegerField(default=0)),
        migrations.AddField(model_name='match', name='scoreboard_offset_ext_y', field=models.IntegerField(default=0)),
    ]
