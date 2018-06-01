'use strict';

$(function () {
    function createCookie(name,value,days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
    }

    function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return decodeURIComponent(c.substring(nameEQ.length,c.length));
        }
        return null;
    }

    function eraseCookie(name) {
        createCookie(name,"",-1);
    }

    $('input[type=checkbox]').on('ifCreated', function(event){
        if($(this).attr('checked')){
            $(this).iCheck('check');
        }
    });

    $('input').not(".filterSearchCheckbox").iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%' // optional
    });

    $('.sidebar-toggle').on('click', function(){
        var collapsed = $('body.sidebar-mini').hasClass('sidebar-collapse') ? 'collapsed' : 'open';
        createCookie("sidebarStatus", collapsed, 99);
    });
   
   $('.navbar-custom-menu').show();

    var sidebarBody = $('body.sidebar-mini');
    if (readCookie("sidebarStatus") == 'collapsed' && !sidebarBody.hasClass('sidebar-collapse')){
        sidebarBody.addClass('sidebar-collapse');
    } else if (sidebarBody.hasClass('sidebar-collapse')) {
        sidebarBody.removeClass('sidebar-collapse');
    }
});