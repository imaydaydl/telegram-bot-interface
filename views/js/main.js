var dark_theme = 'false';
if(localStorage.getItem('dark_theme')) dark_theme = localStorage.getItem('dark_theme');

var h = (new Date()).getHours();

$(document).ready(function(){
    $('.basic-multiple').select2();

    changeTheme();
    changeActive();

    if (h > 23 || h <7) $('#hello_text').html('Доброї ночі');
    if (h > 6 && h < 12) $('#hello_text').html('Доброго ранку');
    if (h > 11 && h < 19) $('#hello_text').html('Доброго дня');
    if (h > 18 && h < 24) $('#hello_text').html('Доброго вечора');

    $('#toggleDarkTheme').on('change', function() {
        dark_theme = dark_theme == 'true' ? 'false' : 'true';
        localStorage.setItem('dark_theme', dark_theme);
        changeTheme();
    });
    $('.create_db').on('click', function() {
        let host = $('#dbhost').val();
        let db = $('#dbname').val();
        let user = $('#dbuser').val();
        let pass = $('#dbpasswd').val() ?? null;
        let adm = $('#user').val();
        let passwd = $('#passwd').val();

        if(host === '') {
            toast('warning', 'Увага!', 'Поле "Сервер MySQL" не може бути пустим');
        } else if(db === '') {
            toast('warning', 'Увага!', 'Поле "Ім\'я бази даних" не може бути пустим');
        } else if(user === '') {
            toast('warning', 'Увага!', 'Поле "Користувач БД" не може бути пустим');
        } else if(adm === '') {
            toast('warning', 'Увага!', 'Поле "Користувач (адмін)" не може бути пустим');
        } else if(passwd === '') {
            toast('warning', 'Увага!', 'Поле "Пароль користувача" не може бути пустим');
        } else {
            $.ajax({
                type: 'POST',
                url: '/install/create_db',
                data: {host: host, db: db, user: user, pass: pass, adm: adm, passwd: passwd},
                dataType: 'json',
                success: function(response) {
                    if(response.status == 'error') {
                        toast('error', 'Помилка!', 'Сталась помилка під час створення конфігурації БД. ' + response.message);
                    } else {
                        $('.status_db').removeClass('text-danger').addClass('text-success');
                        if(response.file_status) $('.status_db').html('Дозволено');
                        $('.chmod_db').html(response.chmod);
                        toast('success', 'Успішно!', 'Конфігурацію БД успішно створено. Сторінку буде перезавантажено');
                        setInterval(function (){
                            window.location = '/';
                        }, 3000);
                    }
                }
            });
        }
    });
    $('.create_config').on('click', function() {
        let tg_bot = $('#tg_token').val() ?? null;
        let tg_url = $('#tg_url').val() ?? null;
        let hash = $('.hash_cr').val();

        if(hash === '') {
            toast('warning', 'Увага!', 'Поле "Хеш" не може бути пустим');
        } else {
            $.ajax({
                type: 'POST',
                url: '/install/create_config',
                data: {hash: hash, tg_bot: tg_bot, tg_url: tg_url},
                dataType: 'json',
                success: function(response) {
                    if(response.status == 'error') {
                        toast('error', 'Помилка!', 'Сталась помилка під час створення конфігурації');
                    } else {
                        $('.configFile').removeClass('text-danger').addClass('text-success');
                        if(response.file_status) $('.configFile').html('Дозволено');
                        $('.chmod_config').html(response.chmod);
                        toast('success', 'Успішно!', 'Конфігурацію успішно створено.');
                    }
                }
            });
        }
    });
    $('.random_hash').on('click', function() {
        let hash = makeid();
        $('.hash_cr').val(hash);
    });
    $('.content_top_menu div').on('click', function() {
        switch($(this).attr('id')) {
            case 'send_message':
            default:
                window.location = '/';
                break;
            case 'history':
                window.location = '/history';
                break;
            case 'log':
                window.location = '/log';
                break;
            case 'user_list':
                window.location = '/users';
                break;
            case 'settings':
                window.location = '/settings';
                break;
        }
    });
    $('.sendMessage').on('click', function() {
        let message = $('.message_tg').val();
        let reciever = $('.recievers').val();

        console.log(reciever);

        if(message == '') {
            toast('warning', 'Увага!', 'Текст повідомлення не може бути пустим.');
        } else {
            $.ajax({
                type: 'POST',
                url: '/bot/message',
                data: {message: message, reciever: reciever},
                dataType: 'json',
                success: function(response) {
                    if(response.status == 'error') {
                        toast('error', 'Помилка!', 'Сталась помилка під час відправлення повідомлення');
                    } else {
                        console.log(response);
                        toast('success', 'Успішно!', 'Повідомлення успішно відправлено.');
                    }
                }
            });
        }
    });
    $('.clearMessage').on('click', function() {
        $('.message_tg').val('');
    });
    $('.login').on('click', function() {
        login();
    });
    $('body').on('click', '.addUser', function() {
	
		$('body').append('<div class="user_modal">'
            + '<div class="modal_content" id="modal-box">'
                + '<div><input type="text" id="new_login" placeholder="Логін"></div>'
                + '<div><input type="password" id="new_pass" placeholder="Пароль"></div>'
                + '<div><input type="password" id="new_repeat_pass" placeholder="Повторіть пароль"></div>'
                + '<p><b>Доступи</b></p>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="senm">Відправка повідомлення</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="senm">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="hs">Історія повідомлень</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="hs">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="lg">Лог користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="lg">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="su">Список користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="su">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="st">Налаштування</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="st">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="au">Додавати користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="au">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="eu">Редагувати користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="eu">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="bu">Блокувати користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="bu">'
                + '</div></div>'
                + '<div class="text-center"><button class="btn btn-primary addNewUser">Додати користувача</button></div>'
            + '</div>'
            + '<i class="icon-close far fa-solid fa-xmark"></i>'
        + '</div>'
        + '<div class="overlay"></div>');
		
		var wbl = $(window).width()-40;
		if( $('.user_modal').width() > wbl ) {
			$('.user_modal').height(wbl/($('.user_modal').width()/$('.user_modal').height())).width(wbl).css({'margin-left': 0-wbl/2});
		}
		
		$('.user_modal').animate({opacity: 1, top: $(window).height()/2-$('.user_modal').height()/2}, 'slow', function() {});
	});
	$('body').on('click', '.user_modal .icon-close, .overlay', function() {
        $('body').find('.overlay').remove();
		$('.user_modal').animate({opacity: 0, top: 0}, function() {
			$(this).remove();
		});
	});
    $('body').on('click', '.addNewUser', function() {
        let new_login = $('body').find('#new_login').val();
        let new_pass = $('body').find('#new_pass').val();
        let new_repeat_pass = $('body').find('#new_repeat_pass').val();

        let json = {};
        json.new_login = new_login;

        if(new_pass != new_repeat_pass) {
            toast('warning', 'Увага!', 'Паролі не співпадають');
        } else {
            json.password = new_pass;
            json.rules = [];
            let pas = false;

            if($('#senm').is(':checked')) {
                json.rules.push(1);
                pas = true;
            }
            if($('#hs').is(':checked')) {
                json.rules.push(2);
                pas = true;
            }
            if($('#lg').is(':checked')) {
                json.rules.push(3);
                pas = true;
            }
            if($('#su').is(':checked')) {
                json.rules.push(4);
                pas = true;
            }
            if($('#st').is(':checked')) {
                json.rules.push(5);
                pas = true;
            }
            if($('#au').is(':checked')) {
                json.rules.push(6);
            }
            if($('#eu').is(':checked')) {
                json.rules.push(7);
            }
            if($('#bu').is(':checked')) {
                json.rules.push(8);
            }

            if(json.rules.length == 0 || !pas) {
                toast('warning', 'Увага!', 'Оберіть хоча б один дозвіл для користувача із меню');
            } else {
                $.ajax({
                    type: 'POST',
                    url: '/user/add',
                    data: json,
                    dataType: 'json',
                    success: function(response) {
                        if(response.status == 'error') {
                            toast('error', 'Помилка!', 'Сталась помилка під час створення користувача. ' + response.message);
                        } else {
                            toast('success', 'Успішно!', 'Користувача успішно створено');
                            $('body').find('.icon-close').click();

                            let ed = '',
                                bl = '';

                            if(response.edit) {
                                ed = '<button class="btn btn-primary" data-id="' + response.id + '" title="Редагувати"><i class="fa-solid fa-pencil"></i></button>';
                            }

                            if(response.block) {
                                bl = '<button class="btn btn-danger" data-id="' + response.id + '" title="Заблокувати"><i class="fa-solid fa-lock"></i></button>';
                            }

                            $('#user_list_block table.history tbody').append('<tr>'
                                + '<td></td>'
                                + '<td></td>'
                                + '<td>' + new_login + '</td>'
                                + '<td>' + response.reg_date + '</td>'
                                + '<td>' + response.rules + '</td>'
                                + '<td>'
                                    + ed
                                    + bl
                                + '</td>'
                            + '</tr>');
                        }
                    }
                });
            }
        }
    });
    $('body').on('click', '.editUser', function() {
        let usid = $(this).data('id');
        let name = $(this).closest('tr').find('td:first-child').html();
        let second = $(this).closest('tr').find('td:nth-child(2)').html();
        let login = $(this).closest('tr').find('td:nth-child(3)').html();

        $('body').append('<div class="user_modal">'
            + '<div class="modal_content" id="modal-box">'
                + '<div><input type="text" id="edit_name" placeholder="Ім\'я" value="' + name + '"></div>'
                + '<div><input type="text" id="edit_second" placeholder="Прізвище" value="' + second + '"></div>'
                + '<div><input type="text" id="edit_login" placeholder="Логін" value="' + login + '"></div>'
                + '<div><input type="password" id="edit_pass" placeholder="Пароль"></div>'
                + '<div><input type="password" id="edit_repeat_pass" placeholder="Повторіть пароль"></div>'
                + '<p><b>Доступи</b></p>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="senm">Відправка повідомлення</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="senm">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="hs">Історія повідомлень</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="hs">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="lg">Лог користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="lg">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="su">Список користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="su">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="st">Налаштування</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="st">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="au">Додавати користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="au">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="eu">Редагувати користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="eu">'
                + '</div></div>'
                + '<div><div class="form-check form-switch">'
                    + '<label class="form-check-label" for="bu">Блокувати користувачів</label>'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="bu">'
                + '</div></div>'
                + '<div class="text-center">'
                    + '<button class="btn btn-primary saveUser" data-id="' + usid + '">Зберегти</button>'
                + '</div>'
            + '</div>'
            + '<i class="icon-close far fa-solid fa-xmark"></i>'
        + '</div>'
        + '<div class="overlay"></div>');
        
        $.ajax({
            type: 'POST',
            url: '/user/userRules',
            data: {user_id: usid},
            dataType: 'json',
            success: function(response) {
                for(const i in response) {
                    switch(response[i]) {
                        case '1':
                            $('body').find('#senm').attr('checked', true);
                            break;
                        case '2':
                            $('body').find('#hs').attr('checked', true);
                            break;
                        case '3':
                            $('body').find('#lg').attr('checked', true);
                            break;
                        case '4':
                            $('body').find('#su').attr('checked', true);
                            break;
                        case '5':
                            $('body').find('#st').attr('checked', true);
                            break;
                        case '6':
                            $('body').find('#au').attr('checked', true);
                            break;
                        case '7':
                            $('body').find('#eu').attr('checked', true);
                            break;
                        case '8':
                            $('body').find('#bu').attr('checked', true);
                            break;
                    }
                }
            }
        });

        var wbl = $(window).width()-40;
		if( $('.user_modal').width() > wbl ) {
			$('.user_modal').height(wbl/($('.user_modal').width()/$('.user_modal').height())).width(wbl).css({'margin-left': 0-wbl/2});
		}
		
		$('.user_modal').animate({opacity: 1, top: $(window).height()/2-$('.user_modal').height()/2}, 'slow', function() {});
    });
    $('body').on('click', '.saveUser', function() {
        let pass = $('body').find('#edit_pass').val();
        let pass_again = $('body').find('#edit_repeat_pass').val();

        if(pass.length > 0 && pass != pass_again) {
            toast('warning', 'Увага!', 'Паролі не співпадають');
        } else {
            let json = {}
            json.user_id = $(this).data('id');
            if($('body').find('#edit_name').val() != '') json.name = $('body').find('#edit_name').val();
            if($('body').find('#edit_second').val() != '') json.second = $('body').find('#edit_second').val();
            json.login = $('body').find('#edit_login').val();
            if(pass.length > 0) json.password = pass;

            json.rules = [];
            let pas = false;

            if($('#senm').is(':checked')) {
                json.rules.push(1);
                pas = true;
            }
            if($('#hs').is(':checked')) {
                json.rules.push(2);
                pas = true;
            }
            if($('#lg').is(':checked')) {
                json.rules.push(3);
                pas = true;
            }
            if($('#su').is(':checked')) {
                json.rules.push(4);
                pas = true;
            }
            if($('#st').is(':checked')) {
                json.rules.push(5);
                pas = true;
            }
            if($('#au').is(':checked')) {
                json.rules.push(6);
            }
            if($('#eu').is(':checked')) {
                json.rules.push(7);
            }
            if($('#bu').is(':checked')) {
                json.rules.push(8);
            }

            if(json.rules.length == 0 || !pas) {
                toast('warning', 'Увага!', 'Оберіть хоча б один дозвіл для користувача із меню');
            } else {
                $.ajax({
                    type: 'POST',
                    url: '/user/edit',
                    data: json,
                    dataType: 'json',
                    success: function(response) {
                        if(response.status == 'error') {
                            toast('error', 'Помилка!', 'Сталась помилка під час редагування користувача. ' + response.message);
                        } else {
                            toast('success', 'Успішно!', 'Нові дані користувача успішно збережено');
                            $('body').find('.icon-close').click();
                            let this_row = $('body').find('button.editUser[data-id="' + json.user_id + '"]');
                            if(json.name != undefined && json.name.length > 0) {
                                this_row.closest('tr').find('td:first-child').html(json.name);
                            }
                            if(json.second != undefined && json.second.length > 0) {
                                this_row.closest('tr').find('td:nth-child(2)').html(json.second);
                            }
                            if(json.login != undefined && json.login.length > 0) {
                                this_row.closest('tr').find('td:nth-child(3)').html(json.login);
                            }

                            this_row.closest('tr').find('td:nth-child(5)').html(response.rules);
                        }
                    }
                });
            }
        }
    });
    $('body').on('click', '.blockUser', function() {
        let this_button = $(this);
        let user_id = $(this).data('id');

        $.ajax({
            type: 'POST',
            url: '/user/block',
            data: {user_id: user_id},
            dataType: 'json',
            success: function(response) {
                if(response.status == 'error') {
                    toast('error', 'Помилка!', 'Сталась помилка під час блокування користувача. ' + response.message);
                } else {
                    let text = '';
                    if(response.new_status == 0) {
                        text = 'заблоковано';
                        this_button.removeClass('btn-danger').addClass('btn-success');
                        this_button.attr('title', 'Розблокувати');
                        this_button.find('i.fa-solid').removeClass('fa-lock').addClass('fa-lock-open');
                    } else {
                        text = 'розблоковано';
                        this_button.removeClass('btn-success').addClass('btn-danger');
                        this_button.attr('title', 'Заблокувати');
                        this_button.find('i.fa-solid').removeClass('fa-lock-open').addClass('fa-lock');
                    }
                    toast('success', 'Успішно!', 'Користувача успішно ' + text);
                }
            }
        });
    });
    $('.editProfile').on('click', function() {
        let user_id = $('.profile_info div:first-child span:nth-child(2)').html();
        let name = $('.profile_info div:nth-child(2) span:nth-child(2)').html();
        let second = $('.profile_info div:nth-child(3) span:nth-child(2)').html();
        let login = $('.profile_info div:nth-child(4) span:nth-child(2)').html();

        $('.content_middle_settup').append('<div class="user_edit">'
            + '<div><input type="text" id="edit_name" placeholder="Ім\'я" value="' + name + '"></div>'
            + '<div><input type="text" id="edit_second" placeholder="Прізвище" value="' + second + '"></div>'
            + '<div><input type="text" id="edit_login" placeholder="Логін" value="' + login + '"></div>'
            + '<div><input type="password" id="old_pass" placeholder="Старий пароль"></div>'
            + '<div><input type="password" id="edit_pass" placeholder="Новий пароль"></div>'
            + '<div><input type="password" id="edit_repeat_pass" placeholder="Повторіть пароль"></div>'
            + '<div><div class="input-group input-group-sm mb-3">'
                + '<span class="input-group-text">Двухфакторна авторизація</span>'
                + '<div class="input-group-text form-check form-switch">'
                    + '<input class="form-check-input" type="checkbox" role="switch" id="userTwoFactory">'
                + '</div>'
            + '</div></div>'
            + '<div class="text_center"><button class="btn btn-primary saveProfile" data-id="' + user_id + '">Зберегти</button></div>'
            + '</div>'
        );

        var wbl = $(window).width()-40;
		if( $('.user_edit').width() > wbl ) {
			$('.user_edit').height(wbl/($('.user_edit').width()/$('.user_edit').height())).width(wbl).css({'margin-left': 0-wbl/2});
		}
		
		$('.user_edit').animate({width: '100%', 'padding-top': '20px'}, 'slow', function() {});
    });

    $('body').on('click', '.saveProfile', function() {
        let user_id = $(this).data('id');
        let old_pass = $('body').find('#old_pass').val();

        if(old_pass == '') {
            toast('warning', 'Увага!', 'Вкажіть старий пароль');
        } else {
            let new_pass = $('body').find('#edit_pass').val();
            let repeat_pass = $('body').find('#edit_repeat_pass').val();

            if(new_pass == old_pass) {
                toast('warning', 'Увага!', 'Старий пароль не може співпадати із новим');
            } else if(new_pass != repeat_pass) {
                toast('warning', 'Увага!', 'Новий пароль та повторний пароль не співпадають');
            } else {
                let name = $('body').find('#edit_name').val();
                let second = $('body').find('#edit_second').val();
                let login = $('body').find('#edit_login').val();

                let json = {};
                json.user_id = user_id;
                json.old_pass = old_pass;
                if(old_pass != '') json.password = new_pass;
                json.name = name;
                json.second = second;
                json.login = login;
                json.two_step = $('body').find('#userTwoFactory').is(':checked') ? 1 : 0;
                json.two_factory_code = $('body').find('#two_factory_code').val() ?? '';

                $.ajax({
                    type: 'POST',
                    url: '/user/saveProfile',
                    data: json,
                    dataType: 'json',
                    success: function(response) {
                        if(response.status == 'error') {
                            toast('error', 'Помилка!', 'Сталась помилка. ' + response.message);
                        } else {
                            toast('success', 'Успішно!', 'Дані успішно оновлено');
                        }
                    }
                });
            }
        }
    });
    $('.logout').on('click', function() {
        $.ajax({
            type: 'POST',
            url: '/user/logout',
            dataType: 'json',
            success: function(response) {}
        });
    });
    $('body').on('click', '.addBotButton', function() {
        $(this).attr('disabled', true);
        $(this).before('<span class="line_new_button"><input type="text" class="button_name" placeholder="Кнопка"><button class="btn btn-success acceptButton"><i class="fa-solid fa-check"></i></button></span>');
    });
    $('body').on('click', '.acceptButton', function() {
        let bact = $(this).closest('span.line_new_button').find('.button_name');
        let but_name = bact.val();

        if(but_name == '') {
            toast('warning', 'Увага!', 'Заповніть назву кнопки');
        } else {
            if(bact.data('id') != undefined && bact.data('id') != '') {
                allButons[bact.data('id')] = bactval;
            } else {
                if(!allButons.includes(but_name)) {
                    allButons.push(but_name);
                }
                for(const i in allButons) {
                    if(allButons[i] == but_name) {
                        bact.attr('data-id', i);
                    }
                }
            }

            bact.attr('data-apr', 'approved');

            $(this).closest('span.line_new_button').append('<button class="btn btn-danger deleteButton"><i class="fa-solid fa-trash"></i></button>');
            $(this).remove();
            toast('success', 'Успішно!', 'Глобальну кнопку додано');
            $('.addBotButton').attr('disabled', false);
        }
    });
    $('body').on('keyup', '.innerButActon, .innerButName', function() {
        if($(this).closest('div.innerButtons').find('.deleteInnerButton').length > 0) { 
            $(this).closest('div.innerButtons').find('.deleteInnerButton').remove();
            $(this).closest('div.innerButtons').append('<button class="btn btn-success acceptInnerButton"><i class="fa-solid fa-check"></i></button>');
        }
    });
    $('body').on('change', '.buttons_selector', function() {
        let b = $(this).val();
        let all = 0;
        $('body').find('.buttons_selector').each(function() {
            if($(this).val() == b) {
                all++;
            }
        });
        if(all > 1) {
            toast('warning', 'Увага!', 'Налаштування на цю кнопку вже створено');
            $(this).val('');
        }
    });
    $('body').on('click', '.deleteCurrentButton', function() {
        $(this).closest('tr').remove();
    });
    $('body').on('click', '.deleteButton', function() {
        let id = $(this).closest('span.line_new_button').find('.button_name').data('id');
        $(this).closest('span.line_new_button').remove();
        if($('body').find('input[data-id="' + id + '"]').length == 0) delete allButons[id];
    });
    $('body').on('keyup', '.button_name', function() {
        if($(this).closest('span').find('.acceptButton').length === 0) {
            $(this).closest('span').append('<button class="btn btn-success acceptButton"><i class="fa-solid fa-check"></i></button>');
            $('.addBotButton').attr('disabled', true);
        }
    });
    $('body').on('click', '.addTextButton', function() {
        $(this).closest('.text_buttons').find('.innerButtonsBlock').append('<div class="innerButtons"><input type="text" class="innerButName" placeholder="Назва кнопки">'
            + '<input type="text" class="innerButActon" placeholder="Дія">'
            + '<button class="btn btn-success acceptInnerButton"><i class="fa-solid fa-check"></i></button></div>');
    });
    $('body').on('click', '.acceptInnerButton', function() {
        let this_but = $(this);
        let this_block = $(this).closest('div.innerButtons');
        let bname = this_block.find('.innerButName').val();
        let bact = this_block.find('.innerButActon');
        let bactval = bact.val();

        if(bname == '') {
            toast('warning', 'Увага!', 'Необхідно вказати назву кнопки');
        } else if(bactval == '') {
            toast('warning', 'Увага!', 'Необхідно вказати дію кнопки');
        } else {
            if(bact.data('id') != undefined && bact.data('id') != '') {
                allButons[bact.data('id')] = bactval;
            } else {
                if(!allButons.includes(bactval)) {
                    allButons.push(bactval);
                }
                for(const i in allButons) {
                    if(allButons[i] == bactval) {
                        bact.attr('data-id', i);
                    }
                }
            }

            bact.attr('data-apr', 'approved');
            this_but.remove();
            this_block.append('<button class="btn btn-danger deleteInnerButton"><i class="fa-solid fa-trash"></i></button>');
            toast('success', 'Успішно!', 'Кнопку до тексту додано');
        }
    });
    $('body').on('click', '.deleteInnerButton', function() {
        let id = $(this).closest('div.innerButtons').find('.innerButActon').data('id');
        $(this).closest('div.innerButtons').remove();
        if($('body').find('input[data-id="' + id + '"]').length == 0) delete allButons[id];
    });
    $('.addNewButton').on('click', function(){
        $('body').find('.menuBlock').last().after('<tr class="menuBlock">'
            + '<td class="bots_settings">'
                + '<div class="line_new_button">'
                    + '<input class="newButtonName" type="text">'
                    + '<button class="btn btn-success saveNewButton"><i class="fa-solid fa-check"></i></button>'
                + '</div>'
            + '</td>'
        + '</tr>');
    });
    $('body').on('click', '.saveNewButton', function() {
        let this_but_name = $(this).closest('.line_new_button').find('.newButtonName').val();

        if(this_but_name == '') {
            toast('warning', 'Увага!', 'Спочатку введіть ім\я кнопки');
        } else {
            allButons.push(this_but_name);

            $(this).closest('.bots_settings').append('<div class="selector_block">'
                    + '<select class="basic-multiple buttons_selector" name="button[]"></select>'
                    + '<button class="btn btn-danger deleteCurrentButton"><i class="fa-solid fa-trash"></i></button>'
                + '</div>'
                + '<div class="fields_buttons">'
                    +'<div class="fields_left">'
                        + '<div class="input-group mb-3">'
                            + '<span class="input-group-text" id="count_days">Як часто можна викликати цю команду</span>'
                            + '<input type="number" class="form-control count_days" placeholder="Кількість днів" aria-describedby="count_days">'
                        + '</div>'
                        + '<textarea class="menuText" placeholder="Текст повідомлення"></textarea>'
                    + '</div>'
                    + '<div class="fields_right">'
                        + '<button class="btn btn-primary addBotButton">Додати глобальну кнопку</button>'
                    + '</div>'
                + '</div>'
                + '<div class="text_buttons">'
                    + '<div class="text-center">'
                        + '<button class="btn btn-primary addTextButton">Додати кнопку до тексту</button>'
                    + '</div>'
                    + '<div class="innerButtonsBlock"></div>'
                + '</div>');

            $(this).closest('.bots_settings').find('.buttons_selector').append('<option value=""></option>');
            for(const i in allButons) {
                $(this).closest('.bots_settings').find('.buttons_selector').append('<option value="' + allButons[i] + '">' + allButons[i] + '</option>');
            }

            $(this).closest('.line_new_button').remove();

        }
    });
    $('body').on('change', '#userTwoFactory', function() {
        if($(this).is(':checked')) {
            $(this).closest('div.user_edit').find('div.text_center').before('<div><input type="text" id="two_factory_code" placeholder="КОД"></div>');

            iziToast.info({
                title: 'Увага!',
                message: "Напишіть закріпленому в налаштуваннях боту команду /authorize. Бот напише код, який необхідно ввести в поле \"КОД\" і після натиснути кнопку \"Зберегти\".",
                close: true,
                position: 'topRight',
                timeout: false,
                buttons: [
                    ['<button fdprocessedid="5si1ub">Закрити</button>', function (instance, toast) {
                        instance.hide({
                            transitionOut: 'fadeOutUp'
                        }, toast, 'buttonName');
                    }]
                ],
                zindex: 9999999999999
            });
        } else {
            $(this).closest('div.user_edit').find('#two_factory_code').closest('div').remove();
        }
    });
    $('.saveBotMenu').on('click', function() {
        let json = {};
        let menu = [];
        let stop = false;
        $('body .bots_settings').each(function() {
            let keyButton = $(this).find('select.buttons_selector').val();
            if(keyButton == '') {
                toast('warning', 'Увага!', 'В селекторі не обрано кнопку, до якої прив\'язується текст')
            } else {
                let keyRequired = $(this).find('.count_days').val() ?? 0;
                let globalButtons = [];
                $(this).find('.button_name').each(function() {
                    if(($(this).data('id') != undefined && $(this).data('id') != '') || $(this).data('id') == '0') {
                        globalButtons.push($(this).val());
                    }
                });
                let innerButtons = [];
                $(this).find('.innerButtons').each(function() {
                    let oneInner = {};
                    if($(this).find('.innerButActon').data('id') != undefined && ($(this).find('.innerButActon').data('id') != '' || $(this).find('.innerButActon').data('id') == '0')) {
                        oneInner['action'] = $(this).find('.innerButActon').val();
                        oneInner['name'] = $(this).find('.innerButName').val();
                        innerButtons.push(oneInner);
                    }
                });

                if(globalButtons.length > 0 && innerButtons.length > 0) {
                    stop = true;
                    toast('warning', 'Увага!', 'Одна кнопка меню може мати або глобальне меню, або меню під текстом.');
                }

                let oneMenu = {};
                oneMenu['key'] = keyButton;
                oneMenu['required'] = keyRequired;
                oneMenu['text'] = $(this).find('.menuText').val();
                oneMenu['global'] = globalButtons;
                oneMenu['inner'] = innerButtons;
                menu.push(oneMenu);
            }
        });

        if(menu.length > 0 && !stop) {
            for(const i in menu) {
                json[i] = menu[i];
            }
            $.ajax({
                type: 'POST',
                url: '/settings/menu',
                data: json,
                dataType: 'json',
                success: function(response) {
                    if(response.status == 'error') {
                        toast('error', 'Помилка!', 'Сталась помилка. ' + response.message);
                    } else {
                        toast('success', 'Успішно!', 'Нове меню бота успішно створено');
                    }
                }
            });
        }
    });
    $('.addMenu').on('click', function() {
        $('body').find('.menuBlock').last().after('<tr class="menuBlock">'
            + '<td class="bots_settings">'
                + '<div class="selector_block">'
                    + '<select class="basic-multiple buttons_selector" name="button[]"></select>'
                    + '<button class="btn btn-danger deleteCurrentButton"><i class="fa-solid fa-trash"></i></button>'
                + '</div>'
                + '<div class="fields_buttons">'
                    +'<div class="fields_left">'
                        + '<div class="input-group mb-3">'
                            + '<span class="input-group-text" id="count_days">Як часто можна викликати цю команду</span>'
                            + '<input type="number" class="form-control count_days" placeholder="Кількість днів" aria-describedby="count_days">'
                        + '</div>'
                        + '<textarea class="menuText" placeholder="Текст повідомлення"></textarea>'
                    + '</div>'
                    + '<div class="fields_right">'
                        + '<button class="btn btn-primary addBotButton">Додати глобальну кнопку</button>'
                    + '</div>'
                + '</div>'
                + '<div class="text_buttons">'
                    + '<div class="text-center">'
                        + '<button class="btn btn-primary addTextButton">Додати кнопку до тексту</button>'
                    + '</div>'
                    + '<div class="innerButtonsBlock"></div>'
                + '</div>'
            + '</td>'
        + '</tr>');

        $('body').find('.menuBlock').last().find('.buttons_selector').append('<option value=""></option>');
        for(const i in allButons) {
            $('body').find('.menuBlock').last().find('.buttons_selector').append('<option value="' + allButons[i] + '">' + allButons[i] + '</option>');
        }
    });
    $('body').on('click', '.connect_webhooks', function() {
        let url = '';
        if(window.location.pathname == '/install') {
            url = '/install/creat_connect';
        } else {
            url = '/settings/creat_connect';
        }

        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            success: function(response) {
                if(response.status == 'error') {
                    toast('error', 'Помилка!', 'Сталась помилка. ' + response.message);
                } else {
                    $.ajax({
                        type: 'GET',
                        url: response.url,
                        success: function(response) {
                            if(response.ok == false) {
                                toast('error', 'Помилка!', response.description);
                            } else {
                                toast('success', 'Успішно!', response.description);
                            }
                        },
                        complete: function(xhr, textStatus) {
                            if(xhr.status == 400) {
                                toast('error', 'Помилка!', 'Неправильний запит ' + response.url);
                            }
                        } 
                    });
                }
            }
        });
    });
    $('.saveSettings').on('click', function() {
        let json = {};
        json.bot_token = $('#tg_token').val();
        json.bot_url = $('#tg_url').val();
        json.chat_id = $('#chat_id').val();
        json.two_factory = $('#twoFactory').is(':checked') ? 1 : 0;

        $.ajax({
            type: 'POST',
            url: '/settings/saveSettings',
            data: json,
            dataType: 'json',
            success: function(response) {
                if(response.status == 'error') {
                    toast('error', 'Помилка!', 'Сталась помилка. ' + response.message);
                } else {
                    toast('success', 'Успішно!', 'Нові налаштування успішно збережено');
                }
            }
        });
    });
});

$(document).keypress(function(event) {
    if(window.location.pathname == '/login') {
        let keycode = event.keyCode || event.which;
        if(keycode == '13') {
            login();
        }
    }
});

function login() {
    let login = $('#login').val();
    let password = $('#password').val();

    if(login == '') {
        toast('warning', 'Увага!', 'Поле Логін має бути заповненим');
    } else if(password == '') {
        toast('warning', 'Увага!', 'Поле Пароль має бути заповненим');
    } else {
        $.ajax({
            type: 'POST',
            url: '/login/autorize',
            data: {login: login, password: password},
            dataType: 'json',
            success: function(response) {
                if(response.status == 'error') {
                    toast('error', 'Помилка!', 'Сталась помилка під час авторизації. ' + response.message);
                } else if(response.status == 'two_step') {
                    toast('warning', 'Увага!', response.message);
                    setInterval(function() {
                        $.ajax({
                            type: 'POST',
                            url: '/login/check_two_step',
                            data: {chat_id: response.chat_id},
                            dataType: 'json',
                            success: function(response) {
                                if(response.status == 'success') {
                                    toast('success', 'Успішно!', 'Авторизація успішна.');
                                    setTimeout(function() {
                                        window.location = '/';
                                    }, 3000);
                                }
                            }
                        });
                    }, 3000);
                } else {
                    toast('success', 'Успішно!', 'Авторизація успішна.');
                    setTimeout(function() {
                        window.location = '/';
                    }, 3000);
                }
            }
        });
    }
}

function toast(type, title, message) {
    switch(type) {
        case 'success':
            iziToast.success({
                title: title,
                message: message,
                close: false,
                position: 'topRight',
                timeout: 3000,
                zindex: 9999999999999
            });
            break;
        case 'error':
            iziToast.error({
                title: title,
                message: message,
                close: false,
                position: 'topRight',
                timeout: 3000,
                zindex: 9999999999999
            });
            break;
        case 'warning':
            iziToast.warning({
                title: title,
                message: message,
                close: false,
                position: 'topRight',
                timeout: 3000,
                zindex: 9999999999999
            });
            break;
    }
}

function makeid() {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < 10) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
        counter += 1;
    }
    return result;
}

function changeTheme() {
    if([false, 'false'].includes(dark_theme)) {
        $('#toggleDarkTheme').attr('checked', false);
        $('body').removeClass('dark_theme');
    }
    if([true, 'true'].includes(dark_theme)) {
        $('#toggleDarkTheme').attr('checked', true);
        $('body').addClass('dark_theme');
    }
}

function changeActive() {
    let pathname = window.location.pathname;
    $('.content_top_menu div.active').removeClass('active');
    $('.content_middle_settup div.active_block').removeClass('active_block');
    switch(pathname) {
        case '':
        case '/':
        default:
            $('#send_message').addClass('active');
            $('#send_message_block').addClass('active_block');
            break;
        case '/history':
            $('#history').addClass('active');
            $('#history_block').addClass('active_block');
            break;
        case '/log':
            $('#log').addClass('active');
            $('#log_block').addClass('active_block');
            break;
        case '/users':
            $('#user_list').addClass('active');
            $('#user_list_block').addClass('active_block');
            break;
        case '/settings':
            $('#settings').addClass('active');
            $('#settings_block').addClass('active_block');
            break;
        case '/profile':
            $('.content_top_menu div.active').removeClass('active');
            break;
    }
}