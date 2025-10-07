(function($){
  'use strict';

  function csvToIds(csv){
    if(!csv) return [];
    return String(csv)
      .split(',')
      .map(function(s){ return parseInt(s.trim(),10); })
      .filter(function(n){ return !isNaN(n) && n > 0; });
  }

  function idsToCsv(ids){
    return (ids||[]).filter(function(n){return !!n;}).join(',');
  }

  function refreshHiddenInput($wrap){
    var ids = [];
    $wrap.find('.ugm-gallery-item').each(function(){
      var id = parseInt($(this).attr('data-id'),10);
      if(!isNaN(id) && id>0) ids.push(id);
    });
    var $input = $wrap.find('input[type="hidden"]');
    $input.val(idsToCsv(ids)).trigger('change');
  }

  function addItem($list, id, thumbHtml){
    var $li = $('<li class="ugm-gallery-item"/>').attr('data-id', id);
    var $thumb = $('<span class="ugm-thumb"/>').html(thumbHtml);
    var $remove = $('<button type="button" class="button-link ugm-remove" aria-label="'+ (window.ugm_gallery_i18n ? ugm_gallery_i18n.remove : 'Remove') +'">&times;</button>');
    $li.append($thumb).append($remove);
    $list.append($li);
  }

  function fetchThumbHtml(attachment){
    // If media frame supplies sizes, use thumbnail/url
    if(attachment && attachment.sizes && attachment.sizes.thumbnail){
      var s = attachment.sizes.thumbnail;
      return '<img src="'+ s.url +'" width="'+ (s.width||80) +'" height="'+ (s.height||80) +'" alt="" />';
    }
    if(attachment && attachment.icon){
      return '<img src="'+ attachment.icon +'" width="80" height="80" alt="" />';
    }
    // Fallback
    return '<span class="ugm-missing-thumb" />';
  }

  function openMediaFrame($wrap){
    var title = (window.ugm_gallery_i18n ? ugm_gallery_i18n.selectImages : 'Select images');
    var frame = wp.media({
      title: title,
      library: { type: 'image' },
      multiple: true,
      button: { text: title }
    });

    frame.on('select', function(){
      var selection = frame.state().get('selection');
      var $list = $wrap.find('.ugm-gallery-list');
      selection.each(function(model){
        var att = model.toJSON();
        // Avoid duplicates
        var exists = false;
        $list.find('.ugm-gallery-item').each(function(){
          if (parseInt($(this).attr('data-id'),10) === att.id) { exists = true; return false; }
        });
        if(!exists){
          addItem($list, att.id, fetchThumbHtml(att));
        }
      });
      refreshHiddenInput($wrap);
    });

    frame.open();
  }

  function initSortable($wrap){
    var $list = $wrap.find('.ugm-gallery-list');
    if(!$list.length || $list.data('sortable-initialized')) return;
    $list.sortable({
      items: '> .ugm-gallery-item',
      placeholder: 'ugm-gallery-sort-placeholder',
      forcePlaceholderSize: true,
      tolerance: 'pointer',
      update: function(){ refreshHiddenInput($wrap); }
    });
    $list.data('sortable-initialized', true);
  }

  $(document).on('click', '.ugm-gallery-select', function(e){
    e.preventDefault();
    var $wrap = $(this).closest('.ugm-gallery-field');
    openMediaFrame($wrap);
  });

  $(document).on('click', '.ugm-gallery-field .ugm-remove', function(e){
    e.preventDefault();
    var $wrap = $(this).closest('.ugm-gallery-field');
    $(this).closest('.ugm-gallery-item').remove();
    refreshHiddenInput($wrap);
  });

  // Initialize existing fields on load
  $(function(){
    $('.ugm-gallery-field').each(function(){
      initSortable($(this));
    });
  });

})(jQuery);
