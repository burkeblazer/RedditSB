// Overrides
jQuery.fn.center = function () {
    this.css("position","absolute");
    this.css("top", Math.max(0, (($(window).height() - $(this).height()) / 2) + 
                                                $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, (($(window).width() - $(this).width()) / 2) + 
                                                $(window).scrollLeft()) + "px");
    return this;
};

Utility.Module.launch('Header', $.noop, '#header-container');
Utility.Module.launch('Footer', $.noop, '#footer-container');
Utility.Module.launch('Banner', $.noop, '#main-container');