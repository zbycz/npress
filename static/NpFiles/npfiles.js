function filelist_init() {
  //files deleter, insertlink
  $("#js-filelist")
    .delegate(".del", "click", function() {
      $(this)
        .parent()
        .fadeOut();
    })
    .delegate(".insertlink", "click", function(event) {
      if (!event.ctrlKey) {
        $("#frmpageEditForm-text").wysiwyg(
          "insertHtml",
          npmacro2tag($(this).attr("data-embed"))
        );
        return false;
      }
    });

  filelist();
}
function filelist() {
  var handleEmptyList = function() {
    var $ul = $(this);
    var $h4 = $ul.prev();
    if ($ul.children().length <= 1) {
      //always contains div.clearitem
      $h4.add($ul).addClass("emptyList");
      $ul.prepend('<div class="item placeholder" />');
    } else {
      $h4.add($ul).removeClass("emptyList");
      $ul.find(".placeholder").remove();
    }
  };

  // files sorter
  $("#js-filelist .list")
    .each(handleEmptyList)
    .sortable({
      items: "> div.item",
      cancel: ".placeholder",
      connectWith: "#js-filelist .list",

      start: function() {
        $("#js-filelist").addClass("ui-dragging");
      },

      //on target list
      receive: function(event, ui) {
        //send request
        var num = $(this).attr("data-num");
        var fid = ui.item.attr("id").split("-")[1];
        var data =
          "changedId=" +
          fid +
          "&num=" +
          num +
          "&" +
          $(this).sortable("serialize");
        $.post($("#js-filelist").attr("data-sortlink"), data);

        //after moving hide file counters in .infoitem
        ui.sender
          .add(this)
          .find(".infoitem small")
          .css("opacity", 0.05);
        ui.sender.data("handled", true);
        handleEmptyList.call(this);
        $(this).append($(".clearitem", this)); //clearitem must be last element
      },

      //on source list
      stop: function(event, ui) {
        $("#js-filelist").removeClass("ui-dragging");
        handleEmptyList.call(this);

        //send data only if not sent in receive
        if ($(this).data("handled")) $(this).data("handled", false);
        else {
          var data = $(this).sortable("serialize");
          $.post($("#js-filelist").attr("data-sortlink"), data);
        }
      }
    });
}

function handleUpload(fileId) {
  var $up = $("#fileupload");
  var $progress = $("#upload-progress");
  var filesAll = Array.from($up[0].files);
  if (!filesAll.length) return;
  $progress.css("display", "inline");

  if (fileId >= filesAll.length) {
    $progress.css("display", "none"); // we are finished
    $up[0].value = "";
    console.log("All files uploaded");
    return;
  }

  var filesDone = filesAll.filter((file, id) => id < fileId);
  var sizeDone = filesDone.reduce((acc, file) => acc + file.size, 0);
  var sizeTotal = filesAll.reduce((acc, file) => acc + file.size, 0);
  $progress.css("display", "inline").attr({ value: sizeDone, max: sizeTotal });

  var formData = new FormData();
  formData.append("files[]", filesAll[fileId]);

  // https://stackoverflow.com/questions/6974684/how-to-send-formdata-objects-with-ajax-requests-in-jquery
  $.ajax({
    url: $up.attr("data-url"),
    type: "POST",
    processData: false,
    contentType: false,
    data: formData,
    error: e => console.warn(e),
    success: payload => {
      console.log("Upload " + fileId + " finished: ", payload);
      jQuery.nette.success(payload);
      $.get($up.attr("data-afterUploadLink"));
    },
    complete: () => handleUpload(fileId + 1),
    xhr: function() {
      // progressbar - http://christopher5106.github.io/web/2015/12/13/HTML5-file-image-upload-and-resizing-javascript-with-progress-bar.html
      var myXhr = $.ajaxSettings.xhr();
      if (!myXhr.upload) return myXhr;
      myXhr.upload.addEventListener(
        "progress",
        e => {
          if (!e.lengthComputable || !filesAll[fileId]) return;
          $progress.css("display", "inline").attr({
            value: sizeDone + (e.loaded / e.total) * filesAll[fileId].size,
            max: sizeTotal
          });
        },
        false
      );
      return myXhr;
    }
  });
}

function np_html5upload() {
  $("#fileupload").change(() => handleUpload(0));
}

$(function() {
  np_html5upload();
  filelist_init();
});
