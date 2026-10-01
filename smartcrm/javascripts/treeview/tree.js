   $('.tree-toggler').click(function () {
	$(this).parent().children('ul.tree').toggle(300);
    });
    //add this line:
    $('.tree-toggler').parent().children('ul.tree').toggle(1000);
    
     
    