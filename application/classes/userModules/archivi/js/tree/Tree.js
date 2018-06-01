function treeGen() {

    var id = $('#__id').val();
    var parentId = $('#parentId').val();
    if ($('.gerarchia').hasClass('allHierarchy')) {
        var getRoot = true;
    }
    else {
        var getRoot = false;
    }

    $(".gerarchia").fancytree({
        extensions: ["dnd", "glyph"],
        checkbox: false,
        clickFolderMode: 1,
        activate: function (event, data) {
            return true;
        },
        glyph: {
            map: {
                doc: "fa fa-align-left",
                docOpen: "fa fa-align-left",
                checkbox: "fa fa-square-o",
                checkboxSelected: "fa fa-check-square",
                checkboxUnknown: "fa fa-share",
                dropMarker: "fa fa-arrow-right",
                expanderClosed: "fa fa-caret-right",
                expanderLazy: "fa fa-caret-right",
                expanderOpen: "fa fa-caret-down",
                folder: "fa fa-folder",
                folderOpen: "fa fa-folder-open",
            }
        },
        dnd: {
            autoExpandMS: 400,
            draggable: {
                zIndex: 1000,
                scroll: false,
                revert: "invalid"
            },
            preventVoidMoves: true,
            preventRecursiveMoves: true,
            dragStart: function (node, data) {
                if (node.parent.children.length > 1) {
                    node.parent.folder = true;
                }
                else {
                    node.parent.folder = false;
                }
                node.parent.renderStatus();
                return true;
            },
            dragEnter: function (node, data) {
                return true;
            },
            dragOver: function (node, data) {
            },
            dragLeave: function (node, data) {

            },
            dragStop: function (node, data) {
                if (node.parent.children.length > 0) {
                    node.parent.folder = true;
                }
                else {
                    node.parent.folder = false;
                }
                node.parent.renderStatus();
            },
            dragDrop: function (node, data) {
                $('#myModalConfirm .modal-body').text('Sicuro di voler proseguire?')
                $('#myModalConfirm').modal().on('hidden.bs.modal', function () {
                    $(this).find('.annulla').unbind( "click" );
                    $(this).find('.ok').unbind( "click" );
                });
                $('.annulla').click(function () {
                    return;
                });
                $('.ok').click(function () {
                    node.setExpanded(true).always(function () {
                        var parentId = data.node.data.id;
                        var childId = data.otherNode.data.id;

                        $.ajax({
                            url: Glizy.ajaxUrl + '&controllerName=archivi.controllers.ajax.ModifyTree',
                            type: 'get',
                            action: 'modify',
                            dataType: 'json',
                            data: {
                                id: childId,
                                parentId: parentId
                            },
                            success: function (data, textStatus, jqXHR) {
                                if (data.status === false) {
                                    alert('Attenzione: operazione non permessa. Controllare i livelli di descrizione dei nodi con i quali si sta operando.');
                                }
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                alert('Attenzione: operazione terminata non correttamente.');
                            }
                        })
                    });

                    if (node.children && node.children.length > 0) {
                        node.folder = true;
                    }
                    else {
                        node.folder = false;
                    }
                    node.renderStatus();
                });
            }
        },
        source: $.ajax({
            url: Glizy.ajaxUrl + '&controllerName=archivi.controllers.ajax.GetTree',
            type: 'get',
            dataType: 'json',
            data: {
                id: id,
                getRoot: getRoot
            }
        })
        ,
        lazyLoad: function (event, data) {
            var node = data.node;
            data.result =
                $.ajax({
                    url: Glizy.ajaxUrl + '&controllerName=archivi.controllers.ajax.GetTreeFromParent',
                    type: 'get',
                    dataType: 'json',
                    data: {
                        id: node.data.id
                    }
                })
            ;
        },
        renderNode: function (event, data) {
            var node = data.node;
            var titolo = $(node.span).find(".fancytree-title");

            $(node.span).find("#containerModify").remove();
            $(node.span).find("#containerDraft").remove();
            $(node.span).find("#containerAdd").remove();
            $(node.span).find("#containerDelete").remove();

            var editButton = $('<span id="containerModify"><i class="btn btn-info btn-flat fa fa-pencil"></i></span>');
            var editDraftButton = $('<span id="containerDraft"><i class="btn btn-default btn-flat fa fa-wrench"></i></span>');
            var addButton = $('<span id="containerAdd"><i class="btn btn-success btn-flat fa fa-plus"></i></span>');
            var deleteButton = $('<span id="containerDelete"><i class="btn btn-danger btn-flat fa fa-times"></i></span>');

            if (node.data.canAdd) {
                addButton.insertBefore(titolo);
            }

            editButton.insertBefore(titolo);

            if (!node.data.canEdit) {
                editButton.find('i').addClass('disabled');
            }

            editDraftButton.insertBefore(titolo);

            if (!node.data.canEditDraft) {
                editDraftButton.find('i').addClass('disabled');
            }

            deleteButton.insertBefore(titolo);

            addButton.hide();
            editButton.hide();
            editDraftButton.hide();
            deleteButton.hide();

            if (node.data.canEdit) {
                editButton.click(function () {
                    location.href = data.node.data.routingEdit;
                });
            }

            if (node.data.canEditDraft) {
                editDraftButton.click(function () {
                    location.href = data.node.data.routingEditDraft;
                });
            }

            addButton.click(function () {
                var id = node.data.id;
                if (id) {
                    $('#myModalConfirm .modal-body').text('Sicuro di voler uscire perdendo le eventuali modifiche non salvate?')
                    $('#myModalConfirm .modal-body').append('<div style="margin: 10px 0 10px 0;">Seleziona un livello di descrizione del nodo che vuoi creare:</div>');
                    $('#myModalConfirm .modal-body').append('<div class="form-group" style="margin-bottom:30px;"><div class="col-sm-12"><select id="levelDescription" name="levelDescription" class="form-control"></select></div></div>');
                    var typeId = data.node.data.type;
                    var parentId = data.node.data.id;
                    $.ajax({
                        url: Glizy.ajaxUrl + '&controllerName=archivi.controllers.ajax.GetLevelType',
                        type: 'get',
                        dataType: 'json',
                        data: {
                            id: parentId,
                            typeId: typeId
                        },
                        success: function (data, textStatus, jqXHR) {
                            html = '';
                            for (var key in data) {
                                html += '<option value="' + data[key].typeId + '">' + data[key].typeName + '</option>';
                            }
                            $('#levelDescription').html(html);
                        }
                    })

                    $('#myModalConfirm').modal().on('hidden.bs.modal', function () {
                        $(this).find('.annulla').unbind( "click" );
                        $(this).find('.ok').unbind( "click" );
                    });

                    $('.annulla').click(function () {
                        return;
                    });
                    $('.ok').click(function () {
                        //TODO redirect al nuovo nodo
                        var levelType = $('#levelDescription').val();
                        $.ajax({
                            url: Glizy.ajaxUrl + '&controllerName=archivi.controllers.ajax.CreateNodeFromParent',
                            type: 'get',
                            dataType: 'json',
                            data: {
                                parentId: parentId,
                                typeId: levelType
                            },
                            success: function (data, textStatus, jqXHR) {
                                window.location.href = data;
                            }
                        })
                    });
                } else {
                    $('#myModalConfirm .modal-body').text('Il presente record va salvato prima di poter procedere allo sviluppo della gerarchia.')
                    $('#myModalConfirm').modal().on('hidden.bs.modal', function () {
                        $(this).find('.annulla').unbind( "click" );
                        $(this).find('.ok').unbind( "click" );
                    });

                    $('.annulla').click(function () {
                        return;
                    });
                    $('.ok').click(function () {
                        return;
                    });
                }
            });

            deleteButton.click(function () {
                if (node.children) {
                    alert('Il livello di descrizione selezionato contiene schede figlie, non è possibile cancellarlo senza prima aver cancellato tutti gli elementi subordinati');
                    return;
                }
                $('#myModalConfirm .modal-body').text('Siete sicuri di voler cancellare il record selezionato?');
                $('#myModalConfirm').modal().on('hidden.bs.modal', function () {
                    $(this).find('.annulla').unbind( "click" );
                    $(this).find('.ok').unbind( "click" );
                });
                $('.annulla').click(function () {
                    return;
                });
                $('.ok').click(function () {
                    var id = node.data.id;
                    var parentId = node.parent.data.id;
                    $.ajax({
                        url: Glizy.ajaxUrl + '&controllerName=archivi.controllers.ajax.Delete',
                        type: 'get',
                        action: 'delete',
                        dataType: 'json',
                        data: {
                            id: id
                        },
                        success: function (data, textStatus, jqXHR) {
                            // nella gerarchia non si fa redirect
                            if (!getRoot) {
                                location.href = data.url;
                            } else {
                                jqXHR.parentId = parentId; 
                            }
                        }
                    });
                });
            });

            $(node.span).hover(function () {
                editButton.css("display", "inline-block");
                editDraftButton.css("display", "inline-block");
                addButton.css("display", "inline-block");
                deleteButton.css("display", "inline-block");
            }, function () {
                editButton.hide();
                editDraftButton.hide();
                addButton.hide();
                deleteButton.hide();
            });
        },
        removeNode: function (event, data) {
            //TODO? non so se necessario
        }
    });
}

$(document).ready(function () {
    treeGen();
    $('#editForm').addClass('form-with-tree');
});

$(document).ajaxComplete(function (event, xhr, settings) {
    var action = settings.data;
    var parentId = xhr.parentId;

    if (settings.url.includes('GetTree&')) {
        var activeNode = $(".gerarchia").fancytree("getActiveNode");
        if (activeNode){
            activeNode.scrollIntoView(true);
        }
    }

    if (action === undefined) {
        action = settings.action;
    }
    if (action !== undefined) {
        var $gerarchia = $(".gerarchia");
        if ($gerarchia.hasClass('allHierarchy')) {
            var getRoot = true;
        }
        else {
            var getRoot = false;
        }
        if (action.indexOf("action=saveDraft&") == 0 || action.indexOf("action=save&") == 0 || action == 'delete' || action == 'modify') {
            var id = getRoot ? parentId : $('#__id').val();

            var source = $.ajax({
                url: Glizy.ajaxUrl + '&controllerName=archivi.controllers.ajax.GetTree',
                type: 'get',
                dataType: 'json',
                data: {
                    id: id,
                    getRoot: getRoot
                }
            });

            var tree = $gerarchia.fancytree('getTree');
            tree.reload(source);
        }
    }
});
