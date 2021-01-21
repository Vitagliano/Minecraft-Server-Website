$(function() {
    console.clear();

    if($('.summernote').length) {
        $('.summernote').summernote({
            height: 300,
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "underline", "clear"]],
                ["fontname", ["fontname"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link", "picture"]],
                ["view", ["fullscreen", "codeview", "help"]]
            ]
        });
    }
});

$('#authenticate').on('submit',function(e){
    e ? e.preventDefault() : false;
    $('.alert').fadeOut();
    $('.progress').fadeIn();
    $('.btn').prop("disabled", true);
    var formSerialize = $(this).serialize();
    setTimeout(function () {
        $.ajax({
            url: "/admin/home/login/auth",
            type:"POST",
            dataType:'JSON',
            data: formSerialize,
            complete: function (e){
                console.clear();
                console.log(e.responseText);
                var result = JSON.parse(e.responseText);
                var status = result.response;
                var message = result.message;
                if(status === "ok"){
                    $('.progress').hide();
                    $('.alert-success').fadeIn().html(message);
                    location.reload();
                }else{
                    $('.progress').hide();
                    $('.alert-danger').fadeIn().html(message);
                }
                $('.btn').prop("disabled", false);
            }
        });
    }, 2000);
    return false;
});

$('.replyTicket').on('submit', function(e) {
    e ? e.preventDefault() : false;
    $.ajax({
        type: 'POST',
        url: "/admin/suporte/index/reply",
        data: $(this).serialize(),
        dataType: 'JSON',
        complete: function (result) {
            var r = JSON.parse(result.responseText);
            console.log(result.responseText);
            if(r.response === "ok"){
                location.reload();
            }else{
                toastr["error"](r.message);
            }
        }
    });
    return false;
});

$('#createMessage').on('submit', function(e) {
    e ? e.preventDefault() : false;
    $.ajax({
        type: 'POST',
        url: "/admin/suporte/mensagens/add",
        data: $(this).serialize(),
        dataType: 'JSON',
        complete: function (result) {
            var r = JSON.parse(result.responseText);
            console.log(result.responseText);
            if(r.response === "ok"){
                location.reload();
            }else{
                toastr["error"](r.message);
            }
        }
    });
    return false;
});



$('.message-delete').on('click', function(e) {
    e ? e.preventDefault() : false;
    $.ajax({
        type: 'POST',
        url: "/admin/suporte/mensagens/delete",
        data: { id: $(this).attr('id') },
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

$('.autoreply').on('change', function () {
    var id = $(this).attr('id');
    $(".autoreply option:selected").each(function() {
        var resposta = $(this).val();
        $.ajax({
            type: 'POST',
            url: "/admin/suporte/index/autoreply",
            data: { 'id': id, 'reply':resposta },
            dataType: 'JSON',
            complete: function (result) {
                console.log(result.responseText);
                var r = JSON.parse(result.responseText);
                if(r.response === "ok"){
                    location.reload();
                }
            }
        });
    });
});

$('.ticket-close').on('click', function(e) {
    e ? e.preventDefault() : false;
    $.ajax({
        type: 'POST',
        url: "/admin/suporte/index/close",
        data: { id: $(this).attr('id') },
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

$('.habGateway').on('change', function (e) {
    var id = $(this).attr('id');
    var mode;

    if($(this).is(':checked'))
    {
        mode = 1;
    }else{
        mode = 0;
    }

    $.ajax({
        url: '/admin/configuracoes/gateways/set',
        type: 'POST',
        dataType: 'JSON',
        data: { 'gateway':id, 'mode':mode },
        complete: function (e) {
            console.clear();
            console.log(e.responseText);
        }
    });
});

$('.setDatabase').on('submit',function(e){
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/configuracoes/databases/save",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            toastr["success"]("Banco de dados atualizado!");
            $('.btn', this).prop("disabled", false);
        }
    });
    return false;
});

$('#addChangelog').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/site/atualizacoes/add",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.status === "ok")
            {
                toastr["success"](result.message);
                setTimeout(function () {
                    window.location.reload();
                }, 1000);
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('.editChange').on('submit', function(e) {
    e ? e.preventDefault() : false;
    var button = $('.edit', this);
    button.html("...");
    $.ajax({
        type: 'POST',
        url: "/admin/site/atualizacoes/edit",
        data: $(this).serialize(),
        dataType: 'JSON',
        complete: function (result) {
            var r = JSON.parse(result.responseText);
            console.log(result.responseText);
            if(r.status == "ok"){
                button.html("<i class='ion-checkmark'></i>");
                setTimeout(function() {
                    button.html("<i class='ion-edit'></i>");
                }, 1500);
            }
        }
    });
    return false;
});

$('.del-change').on('click', function(e) {
    e ? e.preventDefault() : false;
    var id = $(this).attr('id');
    $.ajax({
        type: 'POST',
        url: "/admin/site/atualizacoes/delete",
        data: { 'id': id },
        complete: function () {
            var $close = ".input-box-" + id;
            $($close).fadeOut();
        }
    });
    return false;
});

$('.setEmails').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/site/emails/edit",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.response === "ok")
            {
                toastr["success"](result.message);
                setTimeout(function () {
                   window.location.reload();
                }, 1000);
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('#addOffice').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/site/equipe/adicionar/office",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.response === "ok")
            {
                toastr["success"](result.message);
                $('#addOffice input').val("");
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('.office-delete').on('click', function(e) {
   e ? e.preventDefault() : false;
   var id = $(this).attr('id');
    $.ajax({
        url: "/admin/site/equipe/delete/office",
        type:"POST",
        data: { 'id':id },
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            location.reload();
        }
    });
    return false;
});

$('.member-delete').on('click', function(e) {
    e ? e.preventDefault() : false;
    var id = $(this).attr('id');
    $.ajax({
        url: "/admin/site/equipe/delete/member",
        type:"POST",
        data: { 'id':id },
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            location.reload();
        }
    });
    return false;
});

$('#addMember').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/site/equipe/adicionar/member",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.response === "ok")
            {
                toastr["success"](result.message);
                $('#addMember input').val("");
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('.editPerm').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/configuracoes/usuarios/edit",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.response === "ok")
            {
                toastr["success"](result.message);
                setTimeout(function () {
                    location.reload();
                }, 1000);
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('#setMaintenance').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/site/manutencao/alterate",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.response === "ok")
            {
                toastr["success"](result.message);
                setTimeout(function () {
                    location.reload();
                }, 1000);
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('.opentab').on('click', function(e) {
    e ? e.preventDefault() : false;

    $('#openmessage').hide();
    $('.closedtab').hide();

    var tab  = $(this).attr('id');
    $("#"+tab+"-tab").fadeIn();
})

$('#setTerms').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/site/termos/template",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.response === "ok")
            {
                toastr["success"](result.message);
                setTimeout(function () {
                    location.reload();
                }, 1000);
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('#givePackage').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/loja/ativar/set",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.response === "ok")
            {
                toastr["success"](result.message);
                setTimeout(function () {
                    location.reload();
                }, 2000);
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('.messageMaintenance').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/site/manutencao/template",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            let result = JSON.parse(e.responseText);
            if(result.response === "ok")
            {
                toastr["success"](result.message);
            }else{
                toastr["error"](result.message);
                $('.btn', this).prop("disabled", false);
            }

        }
    });
    return false;
});

$('.setGateway').on('submit',function(e){
    e ? e.preventDefault() : false;
    $('.alert').fadeOut();
    $('.btn', this).prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/configuracoes/gateways/save",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            toastr["success"]("Gateway atualizado!");
            $('.btn', this).prop("disabled", false);
        }
    });
    return false;
});

$('#addRate').on('submit',function(e){
    e ? e.preventDefault() : false;
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/loja/capital/addrate",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.log(e.responseText);
            location.reload();
        }
    });
    return false;
});

$('#addUser').on('submit',function(e){
    e ? e.preventDefault() : false;
    $('.alert').fadeOut();
    $('.progress').fadeIn();
    $('.btn').prop("disabled", true);
    var formSerialize = $(this).serialize();
    $.ajax({
        url: "/admin/configuracoes/usuarios/add",
        type:"POST",
        dataType:'JSON',
        data: formSerialize,
        complete: function (e){
            console.clear();
            console.log(e.responseText);
            var result = JSON.parse(e.responseText);
            var status = result.response;
            var message = result.message;
            if(status === "ok"){
                $('.progress').hide();
                $('.alert-success').fadeIn().html(message);
                location.reload();
            }else{
                $('.progress').hide();
                $('.alert-danger').fadeIn().html(message);
            }
            $('.btn').prop("disabled", false);
        }
    });
    return false;
});

$('.approve-purchase').on('click', function (e) {
    e ? e.preventDefault() : false;
    $(this).prop('disabled', true);
    $.ajax({
        url: '/admin/loja/transacoes/approve',
        type: 'POST',
        data: { id: $(this).attr('id') },
        dataType: 'JSON',
        complete: function (e) {
            const result = JSON.parse(e.responseText);
            if(result.response === 'error')
            {
                $(this).prop("disabled", false);
            }else{
                toastr["success"](result.message);
            }
        }
    })
});

$('.backup-save').on('click', function (e) {
    e ? e.preventDefault() : false;
    $(this).prop('disabled', true);
    $.ajax({
        url: '/admin/configuracoes/backup/manual',
        type: 'GET',
        dataType: 'JSON',
        complete: function (e) {
            const result = JSON.parse(e.responseText);
            toastr["success"](result.message);
            setTimeout(function () {
                location.reload();
            }, 1000);
            $(this).prop("disabled", false);
        }
    })
});

$('.backup-download').on('click', function (e) {
    e ? e.preventDefault() : false;
    const id = $(this).attr('id');

    window.open("/admin/configuracoes/backup/download/" + id, "_blank");
});

$('.user-delete').on('click', function(e) {
    e ? e.preventDefault() : false;

    const id = $(this).attr('id');
    $(this).prop("disabled", true);

    $.ajax({
        url: '/admin/configuracoes/usuarios/delete',
        data: { 'id': id },
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            setTimeout(function () {
                location.reload();
            }, 1000);
            $(this).prop("disabled", false);
        }
    })

});

function randomPassword()
{
    $.get(
        '/admin/configuracoes/usuarios/hash',
        function( data ) {
            $('#addUserPassword').val(data);
        }
    );
    return false;
}

$('#addPost').on('submit', function (e) {
    e ? e.preventDefault() : false;
    var formData = new FormData(this);
    $.ajax({
        url: '/admin/site/postagens/add',
        type: 'post',
        processData: false,
        contentType: false,
        data: formData,
        dataType: 'JSON',
        cache: false,
        xhr: function() {
            var myXhr = $.ajaxSettings.xhr();
            if (myXhr.upload) {
                toastr["info"]("Fazendo upload...");
            }
            return myXhr;
        },
        complete: function(result) {
            console.log(result.responseText);
            var response = JSON.parse(result.responseText);

            if(response.response === 'ok')
            {
                toastr["success"](response.message);
                setTimeout(function () {
                    location.reload();
                }, 3000)
            }else
            {
                toastr["warning"](response.message);
            }
        }
    });
});

$('.editPost').on('submit', function (e) {
    e ? e.preventDefault() : false;
    var formData = new FormData(this);
    $.ajax({
        url: '/admin/site/postagens/edit',
        type: 'post',
        processData: false,
        contentType: false,
        data: formData,
        dataType: 'JSON',
        cache: false,
        xhr: function() {
            var myXhr = $.ajaxSettings.xhr();
            if (myXhr.upload) {
                toastr["info"]("Fazendo upload...");
            }
            return myXhr;
        },
        complete: function(result) {
            console.log(result.responseText);
            var response = JSON.parse(result.responseText);

            if(response.response === 'ok')
            {
                toastr["success"](response.message);
                setTimeout(function () {
                    location.reload();
                }, 3000)
            }else
            {
                toastr["warning"](response.message);
            }
        }
    });
});

$('.editStaff').on('submit', function (e) {
    e ? e.preventDefault() : false;
    $.ajax({
        url: '/admin/site/equipe/edit',
        type: 'post',
        data: $(this).serialize(),
        dataType: 'JSON',
        complete: function(result) {
            console.log(result.responseText);
            var response = JSON.parse(result.responseText);

            if(response.response === 'ok')
            {
                toastr["success"](response.message);
            }else
            {
                toastr["warning"](response.message);
            }
        }
    });
});

var $imgPicker = $('.imagePicker');

if($imgPicker.length) {
    $imgPicker.on('change', function (evt) {
        var tgt = evt.target || window.event.srcElement,
            files = tgt.files;

        if (FileReader && files && files.length) {
            var fr = new FileReader();
            fr.onload = function () {
                $('.changeImage').prop('src', fr.result);
            };
            fr.readAsDataURL(files[0]);
        }
    });
}

$('#addCommand').on('click', function (e) {
    e ? e.preventDefault() : false;
    var n = $('#commands .row').length + 1;

    if(n > 0)
    {
        $('#noCommands').hide();
    }

    $.get('/admin/loja/pacotes/liserver', function(e) {
        var li = e;
        var div = "<div class=\"row\" id=\"command-list-"+ n +"\">\n" +
            "                                    <div class=\"col-2\">\n" +
            "                                        <label>Tipo</label>\n" +
            "                                        <select name=\"when[]\" class=\"form-control\">\n" +
            "                                            <option value=\"1\">Aprovar</option>\n" +
            "                                            <option value=\"2\">Expirar</option>\n" +
            "                                            <option value=\"3\">Estorno</option>\n" +
            "                                        </select>\n" +
            "                                    </div>\n" +
            "                                    <div class=\"col-2\">\n" +
            "                                        <label>Servidor</label>\n" +
            "                                        <select name=\"to[]\" class=\"form-control\">\n" +
            "                                            <option value=\"atual\" selected>Principal</option>\n "+ li +
            "                                        </select>\n" +
            "                                    </div>\n" +
            "                                    <div class=\"col-6\">\n" +
            "                                        <label>Comando</label>\n" +
            "                                        <input class=\"form-control\" placeholder=\"utilize %p% para referir ao nickname\" name=\"command[]\">\n" +
            "                                    </div>\n" +
            "                                    <div class=\"col-2\">\n" +
            "                                        <button class=\"btn btn-sm btn-danger\" onclick='commandRemove("+n+")' style=\"margin-top: 35px;\">remover comando</button>\n" +
            "                                    </div>\n" +
            "<div class='col-md-12'><br></div>"+
            "                                </div>";
        $('#commands').append(div);
    });




});

function commandRemove(id)
{
    var div = '.row #command-list-' + id;

    $(div).remove();

    var n = $('#commands .row').length;

    if(n === 0)
    {
        $('#noCommands').show();
    }
}

$('#addServer').on('submit', function(e) {
    e ? e.preventDefault() : false;

    $('.alert').hide();

    $.ajax({
        url: '/admin/loja/servidores/add',
        data: $(this).serialize(),
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            e = JSON.parse(e.responseText);
            if(e.response === 'ok')
            {
                $('#addServer input').val('');
                $('.alert-success').fadeIn().html(e.message);
            }else{
                $('.alert-danger').fadeIn().html(e.message);
            }
        }
    })

});

$('.editServer').on('submit', function(e) {
    e ? e.preventDefault() : false;

    $.ajax({
        url: '/admin/loja/servidores/edit',
        data: $(this).serialize(),
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            e = JSON.parse(e.responseText);
            if(e.response === 'ok')
            {
                toastr["success"](e.message);
            }else{
                toastr["warning"](e.message);
            }
        }
    });

    return false;

});

$('.post-delete').on('click', function(e) {
    e ? e.preventDefault() : false;

    const id = $(this).attr('id');
    $(this).prop("disabled", true);

    $.ajax({
        url: '/admin/site/postagens/delete',
        data: { 'id': id },
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            e = JSON.parse(e.responseText);
            if(e.response === 'ok')
            {
                location.reload();
            }
        }
    })

});

$('.server-delete').on('click', function(e) {
    e ? e.preventDefault() : false;

    const id = $(this).attr('id');
    $(this).prop("disabled", true);

    $.ajax({
        url: '/admin/loja/servidores/delete',
        data: { 'id': id },
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            e = JSON.parse(e.responseText);
            if(e.response === 'ok')
            {
                var th = "#card-table-" + id;
                $(th).fadeOut();
            }
        }
    })

});

$('#addPackage').on('submit', function (e) {
    e ? e.preventDefault() : false;
    var formData = new FormData(this);
    $('.alert').hide();
    $.ajax({
        url: '/admin/loja/pacotes/add',
        type: 'post',
        processData: false,
        contentType: false,
        data: formData,
        dataType: 'JSON',
        cache: false,
        xhr: function() {
            var myXhr = $.ajaxSettings.xhr();
            if (myXhr.upload) {
                toastr["info"]("Fazendo upload...");
            }
            return myXhr;
        },
        complete: function(result) {
            console.log(result.responseText);
            var e = JSON.parse(result.responseText);
            if(e.response === 'ok')
            {
                $('.alert-success').fadeIn().html(e.message);
                setTimeout(function () {
                    window.location.reload();
                }, 1000);
            }else{
                $('.alert-danger').fadeIn().html(e.message);
            }
        }
    });
});

$('.editPackage').on('submit', function (e) {
    e ? e.preventDefault() : false;
    var formData = new FormData(this);
    $('.alert').hide();
    $.ajax({
        url: '/admin/loja/pacotes/edit',
        type: 'post',
        processData: false,
        contentType: false,
        data: formData,
        dataType: 'JSON',
        cache: false,
        xhr: function() {
            var myXhr = $.ajaxSettings.xhr();
            if (myXhr.upload) {
                toastr["info"]("Fazendo upload...");
            }
            return myXhr;
        },
        complete: function(result) {
            console.log(result.responseText);
            var e = JSON.parse(result.responseText);
            if(e.response === 'ok')
            {
                toastr["success"](e.message);
            }else{
                toastr["warning"](e.message);
            }
        }
    });
});

$('.package-delete').on('click', function(e) {
    e ? e.preventDefault() : false;

    const id = $(this).attr('id');
    $(this).prop("disabled", true);

    $.ajax({
        url: '/admin/loja/pacotes/delete',
        data: { 'id': id },
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            e = JSON.parse(e.responseText);
            if(e.response === 'ok')
            {
                var th = "#card-table-" + id;
                $(th).fadeOut();
            }
        }
    })

});

$("#addDiscount #type").on('click', function() {
    var value = $("option:selected", this).val();

    var cupom   = $('#addDiscount #cupom');
    var server  = $('#addDiscount #server');
    var use     = $('#addDiscount #use');

    $('#addDiscount #expire').prop("disabled", false);

    cupom.prop("disabled", true);
    server.prop("disabled", true);
    use.prop("disabled", true);

    $('#addDiscount #percent').prop("disabled", false);

    if(value == "1")
    {
        cupom.prop("disabled", false);
    }
    if(value == "3")
    {
        server.prop("disabled", false);
    }
    if(value == "2")
    {
        use.prop("disabled", false);
        cupom.prop("disabled", false);
        $('#addDiscount #expire').prop("disabled", true);
    }
    return false;
});

$('#addDiscount').on('submit', function (e) {
    e ? e.preventDefault() : false;
    var formData = $(this).serialize();
    $.ajax({
        url: '/admin/loja/descontos/add',
        type: 'post',
        data: formData,
        dataType: 'JSON',
        cache: false,
        complete: function(result) {
            console.log(result.responseText);
            var e = JSON.parse(result.responseText);
            if(e.response === 'ok')
            {
                toastr["success"](e.message);
            }else{
                toastr["warning"](e.message);
            }
        }
    });
    return false;
});

$('.discount-delete').on('click', function(e) {
    e ? e.preventDefault() : false;

    const id = $(this).attr('id');
    $(this).prop("disabled", true);

    $.ajax({
        url: '/admin/loja/descontos/delete',
        data: { 'id': id },
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            e = JSON.parse(e.responseText);
            if(e.response === 'ok')
            {
                var th = "#card-table-" + id;
                $(th).fadeOut();
            }
        }
    })

});

$('#addExpenses').on('submit', function (e) {
    e ? e.preventDefault() : false;
    var formData = $(this).serialize();
    $.ajax({
        url: '/admin/loja/capital/addexpense',
        type: 'post',
        data: formData,
        dataType: 'JSON',
        cache: false,
        complete: function(result) {
            console.log(result.responseText);
            var e = JSON.parse(result.responseText);
            if(e.response === 'ok')
            {
                location.reload();
            }else{
                toastr["warning"](e.message);
            }
        }
    });
    return false;
});

$('.setpaid-expense').on('click', function(e) {
    e ? e.preventDefault() : false;

    const id = $(this).attr('id');
    $(this).prop("disabled", true);

    $.ajax({
        url: '/admin/loja/capital/paidexpense',
        data: { 'id': id },
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            e = JSON.parse(e.responseText);
            if(e.response === 'ok')
            {
                window.location.reload();
            }
        }
    })

});

$('#addBlock').on('submit', function (e) {
    e ? e.preventDefault() : false;
    var formData = $(this).serialize();
    $.ajax({
        url: '/admin/loja/bloquear/add',
        type: 'post',
        data: formData,
        dataType: 'JSON',
        cache: false,
        complete: function(result) {
            console.log(result.responseText);
            var e = JSON.parse(result.responseText);
            if(e.response === 'ok')
            {
                location.reload();
            }else{
                toastr["warning"](e.message);
            }
        }
    });
    return false;
});

$('.block-delete').on('click', function(e) {
    e ? e.preventDefault() : false;

    const id = $(this).attr('id');
    $(this).prop("disabled", true);

    $.ajax({
        url: '/admin/loja/bloquear/delete',
        data: { 'id': id },
        dataType: 'JSON',
        method: 'POST',
        complete: function (e) {
            e = JSON.parse(e.responseText);
            if(e.response === 'ok')
            {
                var th = "#card-table-" + id;
                $(th).remove();
            }
        }
    })

});

$(".input2head").focusout(function(){
    var head = $(this).val();
    $('#head').prop('src', 'https://minotar.net/helm/'+head+'/150.png')
});

$('.serverselect').on('change', function(){
    var id  = $('.serverselect option:selected').val();
    var div = $("#package");

    div.html("<option>aguarde...</option>");
    div.prop('disabled', true);

    $.ajax({
        url: "/admin/loja/ativar/list",
        type:"POST",
        data: {'id': id},
        complete: function (result){
            div.prop('disabled', false);
            div.html(result.responseText);
        }
    });
});