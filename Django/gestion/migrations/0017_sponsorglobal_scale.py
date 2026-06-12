from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('gestion', '0016_reglagesecran'),
    ]

    operations = [
        migrations.AddField(
            model_name='sponsorglobal',
            name='scale',
            field=models.IntegerField(default=100, help_text='Taille individuelle en % (20-300), partagée entre tous les matchs'),
        ),
    ]
