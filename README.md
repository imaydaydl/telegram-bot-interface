# Telegram bot with interface

------------
###### English
For the script to work, you need to install this unofficial library: [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk "telegram-bot-sdk"),
[Library documentation](https://telegram-bot-sdk.readme.io/docs "Library documentation")

Install the library using the composer command:

```shell
composer require irazasyed/telegram-bot-sdk ^2.0
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
Щоб проект працював, вам необхідно встановити неофіційну бібліотеку: [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk "telegram-bot-sdk"), [Документація бібліотеки](https://telegram-bot-sdk.readme.io/docs "Документація бібліотеки")

Встановіть бібліотеку за допомогою наступної composer команди:

```shell
composer require irazasyed/telegram-bot-sdk ^2.0
```

Зареєструйте свого бота у [BotFather](https://t.me/BotFather "BotFather") та вкажіть токен під час першого запуску проекту або в налаштуваннях проекту або в файлі data/config.php:

```php
$config = array(
	'bot_token' => 'Your-Token'
);
```

Посилання для активації WebHooks:

https://api.telegram.org/botТОКЕН_БОТА/setWebHook?url=https://посилання_на_сайт/шлях_до_файлу_бота

