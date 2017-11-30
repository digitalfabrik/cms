/*  Author Chat  v1.6.0  */
/**************************/

var authorChat = function ()
{
    /* Creates a reference to this variable to be used within the functions */
    var $this = this;

    /* init the vars */
    $this.count = 0;
    $this.title = document.title;
    $this.interval_id = null;
    $this.interval_secs = parseInt(localize.set_interval); /* interval time in seconds to check for some new message */
    $this.last_date = '';
    $this.today_date = new Date(Date.now());
    $this.last_id = 0;
    $this.total_rows = 0;
    $this.scroll_id = null;
    $this.win_is_focus = true;
    $this.truncate = [35, 10]; /* to truncate the length of url text */
    $this.uid_list = '';
    $this.uid_colors = {};
    $this.uid_last_color = 1;

    /* list of ASCII Emoticons to detect */
    $this.emoticons = [
        [/(^|\s)(0:\)|0:\-\)|O:\))/gi, 'angel'],
        [/(^|\s)(>:\(|>:\-\()/gi, 'angry'],
        [/(^|\s)(:S|:'\-S)/gi, 'confused'],
        [/(^|\s)(:´\(|:\&#39;\()/gi, 'cry'],
        [/(^|\s)(\(6\)|>:\)|>:\-\))/gi, 'devil'],
        [/(^|\s)(:\||:\-\|)/gi, 'disappont'],
        [/(^|\s)(^|\s)(:\/|:\-\/)/gi, 'doubt'],
        [/(^|\s)(:D|:\-D)/gi, 'happy'],
        [/(^|\s)(XD|X\-D|=D)/gi, 'happy-x'],
        [/(^|\s)(:\{|:\-\{)/gi, 'pain'],
        [/(^|\s)(:\(|:\-\()/gi, 'sad'],
        [/(^|\s)(:\)|:\-\))/gi, 'smiley'],
        [/(^|\s)(=\)|=\-\))/gi, 'smiley-x'],
        [/(^|\s)(:o|:\-o)/gi, 'suprice'],
        [/(^|\s)(:P|:\-P)/gi, 'tongle'],
        [/(^|\s)(XP|X\-P|=P)/gi, 'tongle-x'],
        [/(^|\s)(;\)|;\-\))/gi, 'wink'],
    ];

    /* list of text url to parse */
    $this.parse_urls = [
        /* images */
        [
            /(^|\s)(https?\:\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|]\.)(jpg|jpeg|png|gif|webp)/gim,
            ' <a href="$2$3" title="$2$3" target="_blank"><img class="ac-img" src="$2$3"></a>'
        ],
        /* complete url */
        [
            /(^|\s)((https?|ftp?):\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|])/gim,
            function (str, p1, p2) {
                return ' <a href="' + p2 + '" title="' + p2 + '" target="_blank">' + truncateString(p2, $this.truncate[0], $this.truncate[1]) + '</a>';
            }
        ],
        /* pseudo-url */
        [
            /(^|\s)(www\.[\S]+(\b|$))/gim,
            function (str, p1, p2) {
                return ' <a href="http://' + p2 + '" title="' + p2 + '" target="_blank">' + truncateString(p2, $this.truncate[0], $this.truncate[1]) + '</a>';
            }
        ],
    ];

    var $_chatArea = jQuery('#author-chat-area');
    var $_dialogContent = jQuery('#author-chat-window');
    var $_topDate = $_chatArea.find('.ac-top-date');

    /* hide the current date label of the top */
    $_topDate.hide();

    /* display name on page */
    jQuery('#author-chat .ac-user').html(localize.you_are + ' <span>' + localize.nickname + '</span>');

    /* watch textarea for key presses */
    jQuery('#author-chat .ac-textarea').keydown(function (event)
    {
        var key = event.which;

        /* all keys including return  */
        if (key >= 33)
        {
            var maxLength = jQuery(this).attr("maxlength");
            var length = this.value.length;

            /* don't allow new content if length is maxed out */
            if (length >= maxLength)
            {
                event.preventDefault();
            }
        }
    });

    /* watch textarea for release of key press */
    jQuery('#author-chat .ac-textarea').keyup(function (event)
    {
        var $me = jQuery(this);

        /* only send in case of press Enter, not if we press Shift + Enter */
        if (event.keyCode == 13 && !event.shiftKey)
        {
            var text = $me.val();
            var maxLength = jQuery(this).attr("maxlength");
            var length = text.length;

            /* send */
            if (length <= maxLength + 1)
            {
                $this.send();
                $me.val('');
            } else
            {
                $me.val(text.substring(0, maxLength));
            }
        }
    });

    /* Set focus on textarea with just click inside the Chat Area */
    $_chatArea.mouseup(function (event) {
        /* don't make focus on right click or if we select some text */
        if (event.which == 1 && window.getSelection().toString() == '')
        {
            jQuery('#author-chat .ac-textarea').focus();
        }

    });

    /* Set onBlur event of current window */
    jQuery(window).blur(function ()
    {
        $this.win_is_focus = false;
    });

    /* Set onFocus event of current window */
    jQuery(window).focus(function ()
    {
        $this.win_is_focus = true;
        if ($this.count)
        {
            /* check if we have the floating window */
            if ($_dialogContent.length)
            {
                /* check if the floating window is minimized */
                if ($_dialogContent.is(":hidden") == false)
                {
                    $this.clearCount();
                }
            } else
            {
                $this.clearCount();
            }
            document.title = $this.title;
        }
    });

    /* Click event of the Button to Scroll to Bottom */
    var $_btnToBottom = $_chatArea.find('.ac-tobottom');
    $_btnToBottom.click(function ()
    {
        $_chatArea.scrollTop($_chatArea.prop('scrollHeight'));
        $_btnToBottom.addClass('ac-hidden');
        $_topDate.hide();
    });

    /* MouseWheel event */
    $_chatArea.on('DOMMouseScroll mousewheel', function (ev) {
        var $me = jQuery(this),
                scroll_top = this.scrollTop,
                scroll_height = this.scrollHeight,
                height = $me.innerHeight(),
                delta = ev.originalEvent.wheelDelta,
                up = delta > 0;

        clearTimeout($this.scroll_id);

        /* show the button to go to bottom */
        if ($_btnToBottom.hasClass('ac-hidden'))
        {
            $_btnToBottom.removeClass('ac-hidden');
        }

        /* displays the current date of visible messages at the top as does Whatsapp */
        /* Note: we set a timeout to check the position of the elements after the scroll is finished  */
        $this.scroll_id = setTimeout(function () {
            var $_prevDate = $me.find('.ac-date').first();
            $me.find('.ac-date').each(function () {
                var top = jQuery(this).position().top;
                if (top < 0)
                {
                    if (top > $_prevDate.position().top)
                    {
                        $_prevDate = jQuery(this);
                    }
                }
            });
            if ($_prevDate.text() != $_topDate.text())
            {
                $_topDate.text($_prevDate.text());
            }
        }, 250);

        /* prevent MouseWheel Scrolling of parent elements if we are on the chat-area */
        var prevent = function () {
            ev.stopPropagation();
            ev.preventDefault();
            ev.returnValue = false;
            return false;
        }
        if (!up && -delta >= scroll_height - height - scroll_top)
        {
            $_btnToBottom.addClass('ac-hidden');
            // Scrolling down, but this will take us past the bottom.
            $me.scrollTop(scroll_height);
            $_topDate.hide();
            return prevent();
        } else if (up && delta > scroll_top)
        {
            // Scrolling up, but this will take us past the top.
            $me.scrollTop(0);
            $_topDate.hide();
            return prevent();
        } else
        {
            if ($_topDate.is(':hidden'))
            {
                $_topDate.show();
            }
        }
    });

};

/* Creates a short reference to the prototype */
var _proto_ = authorChat.prototype;

/* Get/Set the seconds for the getState interval */
_proto_.intervalSecs = function (seconds)
{
    this.interval_secs = seconds || this.interval_secs;
    return this.interval_secs
}

/* Start the getState interval */
_proto_.start = function (seconds)
{
    var $this = this;

    /* set the new interval time, if we define one in the arguments */
    $this.intervalSecs(seconds);

    /* stop the current interval, if we have one */
    $this.stop();

    /* start a new interval time */
    $this.interval_id = setInterval(function ()
    {
        $this.getState();
    },
            $this.interval_secs * 1000);
};

/* Stop the getState interval */
_proto_.stop = function ()
{
    var $this = this;
    clearInterval($this.interval_id);
};

/* Clear the Counter */
_proto_.clearCount = function ( )
{
    this.count = 0;
}

/* Update chat if needed */
_proto_.getState = function ()
{
    var $this = this;

    var path = document.location.href.replace(/^https?:\/\/[^\/]+/, '');
    var master_path = $this.getLocalData('ac_master_path');

    /* check if this window is the master or will be the new master in case there is none */
    if (master_path === null || master_path == path)
    {
        /* console.log( 'getState: call Ajax from: '  + path ); */

        /* save the master path to localstorage with a lifetime relative to the current refresh time interval */
        $this.setLocalData('ac_master_path', path, $this.interval_secs * 3);

        jQuery.ajax(
                {
                    type: 'POST',
                    data:
                            {
                                'function': 'getState'
                            },
                    dataType: 'json',
                    success: function (data)
                    {
                        if (data != null)
                        {
                            if (data != $this.total_rows)
                            {
                                /* console.log( 'getState: update from ajax' ); */

                                $this.setLocalData('ac_total_rows', data);
                                $this.total_rows = data;
                                $this.update();
                            }
                        }
                    },
                });
    }
    /* we are not the master window so.. */
    else
    {
        var total_rows = $this.getLocalData('ac_total_rows');
        if (total_rows !== null && parseInt(total_rows) != $this.total_rows)
        {
            /* console.log( 'getState: update from local data' ); */

            $this.total_rows = total_rows;
            $this.update();
        }
    }

};

/* Send the message */
_proto_.send = function ()
{
    var $this = this;
    var message = jQuery('#author-chat .ac-textarea').val();
    jQuery.ajax(
            {
                type: 'POST',
                data:
                        {
                            'function': 'send',
                            'message': message.slice(0, -1),
                            'nickname': localize.nickname,
                            'user_id': localize.user_id
                        },
                dataType: 'json',
                success: function (data)
                {
                    $this.update();
                },
            });
};

/* Updates the chat */
_proto_.update = function ()
{
    var $this = this;

    $this.today_date = new Date(Date.now());

    jQuery.ajax(
            {
                type: 'POST',
                data:
                        {
                            'function': 'update'
                        },
                dataType: 'json',
                success: function (data)
                {
                    if (data != null)
                    {
                        /* get the total rows of the database */
                        var rows = data.id.length;

                        for (var i = 0; i < rows; i++)
                        {
                            /* only show new message */
                            if (parseInt(data.id[i]) <= $this.last_id)
                                continue;

                            /* add the message to the chat-area */
                            $this.showMsg(data.uid[i], data.nick[i], data.msg[i], data.date[i], true);

                            /* check if we are in the floating window */
                            var $_dialogContent = jQuery('#author-chat-window');

                            /* Increments the counter if the current window is not active or if the floating window is minimized */
                            if ($this.win_is_focus == false || ($_dialogContent.length && $_dialogContent.is(":hidden")))
                            {
                                $this.count++;

                                /* yeap, we check again this */
                                if ($_dialogContent.length && $_dialogContent.is(":hidden"))
                                {
                                    var DialogTitleBar = $_dialogContent.parent().find('.ui-dialog-titlebar');
                                    /* we make the titlebar brink if it's not */
                                    if (DialogTitleBar.hasClass('ac-bg-blink') == false)
                                    {
                                        DialogTitleBar.addClass('ac-bg-blink');
                                    }
                                    /* and show the counter */
                                    jQuery('#author-chat-count').text($this.count).show();
                                }

                                document.title = '(' + $this.count + ')' + $this.title;

                                /* playing alert sound */
                                document.getElementById('author-chat-sound').play();
                            }
                        }

                        /* save the last menssage id */
                        $this.last_id = parseInt(data.id[ rows - 1 ]);

                        /* set the last_day with today date */
                        $this.last_date = (data.date[ rows - 1 ].split(','))[0];
                    }

                    /* scroll the chat area to the bottom */
                    $this.scrollToBottom();
                }
            });
};

/* Initiate the chat */
_proto_.initiate = function (seconds)
{
    var $this = this;

    $this.today_date = new Date(Date.now());

    jQuery.ajax(
            {
                type: 'POST',
                data:
                        {
                            'function': 'initiate'
                        },
                dataType: 'json',
                success: function (data)
                {
                    if (data != null && data.id.length)
                    {
                        var rows = data.id.length;

                        for (var i = 0; i < rows; i++)
                        {
                            $this.showMsg(data.uid[i], data.nick[i], data.msg[i], data.date[i]);
                        }
                        /* save the last menssage id */
                        $this.last_id = parseInt(data.id[ rows - 1 ]);
                        /* set the last_day */
                        $this.last_date = (data.date[ rows - 1 ].split(','))[0];
                    }
                    /* scroll the chat area to the bottom */
                    $this.scrollToBottom();
                }
            });

    jQuery('#author-chat').show();

    $this.start(seconds);
};

/* Scroll to the bottom the chat area */
_proto_.scrollToBottom = function ( )
{
    var $this = this,
            $_charArea = jQuery('#author-chat-area'),
            scroll_top = $_charArea.scrollTop(),
            scroll_height = $_charArea.prop('scrollHeight'),
            height = $_charArea.innerHeight();

    $this.scroll_id = null;

    if (scroll_top != scroll_height - height)
    {
        /* add a timeout to check again in case of some attached images have not been loaded */
        $this.scroll_id = setTimeout(function () {
            $this.scrollToBottom();
        }, 2000);
    }
    /* scroll the chat area to the bottom */
    $_charArea.scrollTop(scroll_height);
    $_charArea.find('.ac-top-date').hide();
}

/* Add the menssage to chat area */
_proto_.showMsg = function (uid, nick, msg, date, is_new)
{
    var $this = this;

    var full_date = date.split(',');

    /* add the date label of current message if it's different from the last */
    if (full_date[0] != $this.last_date)
    {
        $this.last_date = full_date[0];
        var msg_date = stringToDate($this.last_date);
        var show_date = msg_date.toLocaleDateString().replace(/\//g, '-').replace(/\-(\d)\-/g, '-0$1-');

        /* change the recent dates to weekday names */
        if (localize.set_weekdays == 1)
        {
            var days = dayDiff($this.today_date, msg_date);
            if (days == 0)
            {
                show_date = localize.today;
            } else if (days == 1)
            {
                show_date = localize.yesterday;
            } else if (days > 1 && days < 7)
            {
                switch (msg_date.getDay())
                {
                    case 0:
                        show_date = localize.sunday;
                        break;
                    case 1:
                        show_date = localize.monday;
                        break;
                    case 2:
                        show_date = localize.tuesday;
                        break;
                    case 3:
                        show_date = localize.wednesday;
                        break;
                    case 4:
                        show_date = localize.thursday;
                        break;
                    case 5:
                        show_date = localize.friday;
                        break;
                    case 6:
                        show_date = localize.saturday;
                        break;
                }
            }
        }

        jQuery('#author-chat-area ul').append(jQuery('<li class="ac-date">' + show_date + '</li>'));
    }

    /* Nick Classes */
    var nick_class = 'ac-nick';
    if (uid == localize.user_id) {
        if (localize.set_show_my_name == 0) {
            nick_class += ' ac-hide';
        }
    } else {
        /* Nick Colors */
        if ($this.uid_list.indexOf(uid) == -1)
        {
            $this.uid_list += uid + ',';
            $this.uid_colors[ uid ] = $this.uid_last_color++;
            if ($this.uid_last_color > 20) {
                $this.uid_last_color = 1;
            }
        }
        nick_class += ' color-' + $this.uid_colors[ uid ];
    }

    /* set the message bubble */
    var $_bubble = jQuery('<li>' + '<div class="ac-time">' + full_date[1] + '</div>' + '<div class="' + nick_class + '">' + nick + '</div>' + '<div class="ac-msg"><span>' + $this.processMsg(msg) + '</span></div>' + '<div class="ac-arrow"></div>' + '</li>');

    /* add the class "ac-me" to the bubble */
    if (uid == localize.user_id) {
        $_bubble.addClass('ac-me');
    }

    /* add thumb preview of last url if have one */
    var $_url = $_bubble.find('a:not(:has(>img))');
    if (localize.set_url_preview == 1 && $_url.length)
    {
        jQuery('<img class="ac-preview" src="https://nyc.searchpreview.de/preview?s=' + $_url.attr('href') + '" />').prependTo($_bubble.find('.ac-msg'));
    }

    /* set init values for the Animation FX for new messages */
    if (is_new) {
        var translateX = (uid == localize.user_id) ? '100%' : '-100%';
        $_bubble.css({transform: 'scaleX(0) scaleY(0) translateX(' + translateX + ')', opacity: 0});
    }

    /* add the message bubble to the chat area */
    jQuery('#author-chat-area ul').append($_bubble);

    /* run the animation FX for new messages */
    if (is_new) {
        setTimeout(function () {
            jQuery('#author-chat-area ul li:last-child').css({transform: 'scaleX(1) scaleY(1) translateX(0%)', opacity: 1});
        }, 150);
    }
};

/* Process the menssage text to add the features */
_proto_.processMsg = function (message)
{
    var $this = this;
    /* Emoticons */
    message = $this.setEmoticons(message);
    /* Links */
    message = $this.setLinks(message);
    /* Shift+Enter */
    message = message.replace(/[\r\n\t\f]/g, '<br>')
    return message;
};

/* Convert ASCII emoticons to image */
_proto_.setEmoticons = function (text)
{
    var $this = this;

    $this.emoticons.forEach(function (value)
    {
        text = text.replace(value[0], '<i class="ac-emo ' + value[1] + '"></i>');
    });

    return text;
};

/* Convert plain URLs to HTML  */
_proto_.setLinks = function (text)
{
    var $this = this;

    $this.parse_urls.forEach(function (value)
    {
        text = text.replace(value[0], value[1]);
    });

    return text;
};

/* Use localStorage or Cookies to manage local data */
if (window['localStorage'] !== null)
{
    /* We use localData */
    _proto_.setLocalData = function (name, value, expires)
    {
        if (expires === undefined && expires === null)
        {
            expires = false;
        } else
        {
            /* make sure expire time it's positive */
            expires = Math.abs(expires);
            /* millisecs since epoch time, lets deal only with integer */
            var now = Date.now();
            var schedule = now + expires * 1000;
        }

        try
        {
            localStorage.setItem(name, value);
            if (expires != false)
            {
                localStorage.setItem(name + '.expires', schedule);
            }
        } catch (e)
        {
            console.log('setLocalData: Error setting name [' + name + '] in localStorage: ' + JSON.stringify(e));
            return false;
        }
        return true;
    };

    _proto_.getLocalData = function (name)
    {
        var $this = this;
        /* epoch time, lets deal only with integer */
        var now = Date.now();

        /* set expiration for storage */
        var expires = localStorage.getItem(name + '.expires');
        if (expires === undefined || expires === null)
        {
            expires = false;
        }

        if (expires != false && expires < now)
        {
            /* Expired */
            $this.removeLocalData(name);
            return null;
        } else
        {
            try
            {
                var value = localStorage.getItem(name);
                return value;
            } catch (e)
            {
                console.log('getStorage: Error reading name [' + name + '] from localStorage: ' + JSON.stringify(e));
                return null;
            }
        }
    };

    _proto_.removeLocalData = function (name)
    {
        try
        {
            localStorage.removeItem(name);
            var expires = localStorage.getItem(name + '.expires');
            if (expires !== undefined || expires !== null)
            {
                localStorage.removeItem(name + '.expires');
            }
        } catch (e)
        {
            console.log('removeStorage: Error removing name [' + name + '] from localStorage: ' + JSON.stringify(e));
            return false;
        }
        return true;
    };
} else
{
    /* We use Cookies */
    _proto_.setLocalData = function (name, value, expires)
    {
        document.cookie = name + ' = ' + value + (expires ? '; max-age=' + expires + ';' : ';');
    };

    _proto_.getLocalData = function (name)
    {
        var name = name + "=";
        var decoded_cookie = decodeURIComponent(document.cookie);
        var ca = decoded_cookie.split(';');

        for (var i = 0; i < ca.length; i++)
        {
            var c = ca[i];
            while (c.charAt(0) == ' ')
            {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0)
            {
                return c.substring(name.length, c.length);
            }
        }
        return null;
    };

    _proto_.removeLocalData = function (name)
    {
        document.cookie = name + '=; max-age=0;';
    };
}


delete _proto_;


/* It allows limiting the length of a string by reducing it when defining a header and ending limit. */
function truncateString(str, ini, end)
{
    if (end)
    {
        return str.length > (ini + end + 3) ? str.substring(0, ini) + '...' + str.substr(end * -1) : str;
    }
    return str.length > ini ? str.substring(0, ini - 3) + '...' : str;
}

/* Convert string date format to date */
function stringToDate(_date, _format, _delimiter)
{
    var format = (_format || 'yyyy-mm-dd').toLowerCase(),
            delimiter = _delimiter || '-',
            formatItems = format.split(delimiter),
            dateItems = _date.split(delimiter),
            monthIndex = formatItems.indexOf('mm'),
            dayIndex = formatItems.indexOf('dd'),
            yearIndex = formatItems.indexOf('yyyy'),
            month = parseInt(dateItems[ monthIndex ]);
    month -= 1;
    return new Date(dateItems[ yearIndex ], month, dateItems[ dayIndex ]);
}

function dayDiff(first_date, second_date) {
    return Math.floor((first_date - second_date) / (1000 * 60 * 60 * 24));
}