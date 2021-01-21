$(function(){

var xhReq = new XMLHttpRequest();
xhReq.open(
  "GET",
  "https://discordapp.com/api/guilds/467543375495823390/widget.json",
  false
);
xhReq.send(null);
var discordjson = JSON.parse(xhReq.responseText);

if (discordjson != null) {
  function showMembersCount() {
    var membersCount = discordjson.members.length;

    var countElem = (document.getElementById("members-count").innerHTML =
      membersCount + "<span class='member-label'> Usuários online<span>");
  }

  function showMembers() {
    discordjson.members.reverse().forEach(function(members) {
      var td = document.createElement("td");

      var label = document.createElement("label");
      label.innerHTML = members.username;

      var img = document.createElement("img");
      img.src = members.avatar_url;

      var table = document.getElementById("members-list");
      var row = table.insertRow(0);
      var td1 = row.insertCell(0);
      var td2 = row.insertCell(1);
      td1.className = "member-avatar";
      td2.className = "member-name";
      td1.appendChild(img);
      td2.appendChild(label);
    });
  }
}



setTimeout(function() {
    showMembersCount();
    showMembers();
}, 500);

/* workaround to display regular table instead of liquid table plugin  */
/* the plugin sets the regular table to display:none, this fixes it */
setTimeout(function() {
document.getElementById("members-list").style.display = "block";
}, 2000);


    $('[data-toggle="tooltip"]').tooltip();

});

$('#cupomApply').on('click',function(e){
    e ? e.preventDefault() : false;

    var hash  = $('#cupom').val();

    $.ajax({
        url:  "/loja/carrinho/cupom",
        type:"POST",
        data: {'hash':hash},
        complete: function (result){
            $('#cupomResult').html(result.responseText);
        }
    });
    return false;
});

$('.add-to-cart').on('click',function(e){
    e ? e.preventDefault() : false;
    var id = $(this).attr('id');
    $.ajax({
        url: "/loja/carrinho/add",
        type:"POST",
        dataType:'JSON',
        data: { 'id':id },
        complete: function (result){
            var r = JSON.parse(result.responseText);
            if(r.response == "ok"){
                window.location.href = "/loja/carrinho";
            }
        }
    });
    return false;
});

$('.remove-from-cart').on('click',function(e){
    e ? e.preventDefault() : false;
    $.ajax({
        url:  "/loja/carrinho/remove",
        type:"POST",
        data: {'id':$(this).attr('id')},
        complete: function (){
            window.location.reload();
        }
    });
    return false;
});

$('#loginAccount').on('submit', function(e) {
    e ? e.preventDefault() : false;
    $('.alert').fadeOut();

    $.ajax({
        url: '/perfil/login/auth',
        type: 'post',
        data: $(this).serialize(),
        dataType: 'JSON',
        complete: function(result) {
            console.log(result.responseText);
            var e = JSON.parse(result.responseText);

            if(e.response == 'ok')
            {
                $('.alert-success').fadeIn().html(e.message);
                location.reload();
            }else
            {
                $('.alert-danger').fadeIn().html(e.message);
            }
        }
    });
    return false;
});

$('#registerAccount').on('submit', function(e) {
    e ? e.preventDefault() : false;
    $('.alert').fadeOut();

    $.ajax({
        url: '/perfil/register/run',
        type: 'post',
        data: $(this).serialize(),
        dataType: 'JSON',
        complete: function(result) {
            console.log(result.responseText);
            var e = JSON.parse(result.responseText);

            if(e.response == 'ok')
            {
                $('.alert-success').fadeIn().html(e.message);
                setTimeout(function () {
                    location.href = "/perfil/login";
                }, 1500);
            }else
            {
                $('.alert-danger').fadeIn().html(e.message);
            }
        }
    });
    return false;
});

$('#openTicket').on('submit', function(e) {
    e ? e.preventDefault() : false;
    $('.alert').fadeOut();

    $.ajax({
        url: '/perfil/suporte/add',
        type: 'post',
        data: $(this).serialize(),
        dataType: 'JSON',
        complete: function(result) {
            console.log(result.responseText);
            var e = JSON.parse(result.responseText);

            if(e.response === 'ok')
            {
                location.reload();
            }else
            {
                $('.alert-danger').fadeIn().html(e.message);
            }
        }
    });
    return false;
});

$('.replyTicket').on('submit', function(e) {
    e ? e.preventDefault() : false;
    $.ajax({
        type: 'POST',
        url: "/perfil/suporte/reply",
        data: $(this).serialize(),
        dataType: 'JSON',
        complete: function (result) {
            var r = JSON.parse(result.responseText);
            console.log(result.responseText);
            if(r.response === "ok"){
                location.reload();
            }else{
                alert(r.message);
            }
        }
    });
    return false;
});

$('.ticket-close').on('click', function(e) {
    e ? e.preventDefault() : false;
    $.ajax({
        type: 'POST',
        url: "/perfil/suporte/close",
        data: { id:$(this).attr('id') },
        dataType: 'JSON',
        complete: function (result) {
            var r = JSON.parse(result.responseText);
            console.log(result.responseText);
            if(r.response === "ok"){
                location.reload();
            }
        }
    });
    return false;
});

$('.att-cart').on('click',function(e){
    e ? e.preventDefault() : false;

    var id  = $(this).attr('id');
    var p = id.split('-');
    var s   = '.quantia-'+p[1];
    var get = $(s).val();

    $.ajax({
        url:  "/loja/carrinho/att",
        type:"POST",
        data: {'id':p[0], 'qnt':get},
        complete: function (result){
            window.location.reload();
        }
    });
    return false;
});

function goTo(str, boolean) {
    if(boolean) {
        return window.open(str, "_blank");
    }
    window.location.href = str;
}

function formatNumber(number)
{
    number = number.toFixed(2) + '';
    x = number.split('.');
    x1 = x[0];
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + '.' + '$2');
    }
    return x1;
}

$('#checkout').click(function() {
    var cupom   = $('#cupom');
    var gateway = $('.gateway:checked');
    var termos  = $("#termos");
    if(!termos.is(':checked')) { return termos.focus(); }
    cupom.prop('disabled', true);
    $(this).prop('disabled', true);
    $.ajax({
        url: "/loja/checkout",
        type:"POST",
        dataType:'JSON',
        data: { gateway: gateway.val(), cupom: cupom.val(), ip: $('#ip').val() },
        complete: function (result){
            console.log(result.responseText);
            var r = JSON.parse(result.responseText);
            if(r.response == "ok"){
                location.href = r.url;
            }else{
                cupom.prop('disabled', false);
                $(this).prop('disabled', false);

            }
        }
    });
});

function copyToClipboard(text) {
    if (window.clipboardData && window.clipboardData.setData) {
        return clipboardData.setData("Text", text);
    } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
        var textarea = document.createElement("textarea");
        textarea.textContent = text;
        textarea.style.position = "fixed";
        document.body.appendChild(textarea);
        textarea.select();
        try {
            return document.execCommand("copy");
        } catch (ex) {
            console.warn("Copy to clipboard failed.", ex);
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    }
}

$('.dropdown').on('show.bs.dropdown', function(e){
    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(300);
});

$('.dropdown').on('hide.bs.dropdown', function(e){
    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(200);
});

$('#menuLoja').mouseenter(function () {
    if($('#menuCart').is( ":hidden" ))
    {
        $('#menuCart').slideDown();
    }
});
$('#menuLoja').mouseleave(function () {
    if($('#menuCart').is( ":visible" ))
    {
        setTimeout(function () {
            $('#menuCart').slideUp();
        }, 5000);
    }
});