$(document).ready(function() {
    $("#sortable input.priority").hide();
		$("#sortable").sortable({
        update: function(event, ui) {
            i = 0;
            $("li", this).each(function() {
                $("input.priority", this).val(i);
                $("label span", this).html(++i);
            });
        }
    });

    $(".menu-item-settings").each(function() {
        var text = $("textarea", this);
        if(text.val() == "") {
            $(this).hide();
        }
        else {
            $(this).parent().addClass("menu-item-edit-active");
        }
    });


    $(".item-controls a").click(function() {
        if($(this).parents("li").hasClass("menu-item-edit-active")) {
            $(this).parents("dl").next().slideUp("fast");
            $(this).parents("li").removeClass("menu-item-edit-active");
        }
        else {
            $(this).parents("dl").next().slideDown("fast");
            $(this).parents("li").addClass("menu-item-edit-active");
        }
    });

//		$("#sortable").disableSelection();
});
