# Telegram bot with interface

------------
###### English
For the script to work, you need to install this unofficial library: [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk "telegram-bot-sdk"),
[Library documentation](https://telegram-bot-sdk.readme.io/docs "Library documentation")

Install the library using the composer command:

```shell
composer install
```

Register your bot in [BotFather](https://t.me/BotFather "BotFather") and insert the token on first run of project or in the settings of project or in the file data/config.php:

```php
$config = array(
	'bot_token' => 'Your-Token'
);
```

Link to activate WebHooks:

https://api.telegram.org/botBOT-TOKEN/setWebHook?url=https://website-address/path-to-bot-script

------------

###### Ukrainian

------------


#  Telegram bot with interface
��� ������ ��������, ��� ��������� ���������� ���������� ��������: [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk "telegram-bot-sdk"), [������������ ��������](https://telegram-bot-sdk.readme.io/docs "������������ ��������")

��������� �������� �� ��������� �������� composer �������:

```shell
composer require install
```

����������� ����� ���� � [BotFather](https://t.me/BotFather "BotFather") �� ������ ����� �� ��� ������� ������� ������� ��� � ������������� ������� ��� � ���� data/config.php:

```php
$config = array(
	'bot_token' => 'Your-Token'
);
```

��������� ��� ��������� WebHooks:

https://api.telegram.org/bot�����_����/setWebHook?url=https://���������_��_����/����_��_�����_����

