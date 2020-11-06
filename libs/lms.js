$(document).ready(function () {
  $(".add-reaction").click(function () {
    var url = $(this).attr("href")
    var nb = $(this).find(".reaction-numbers")
    var nbInit = parseInt(nb.text())
    if (url !== "#") {
      if ($(this).hasClass("user-reaction")) {
        nb.text(nbInit - 1)
        $(this).removeClass("user-reaction")
      } else {
        var previous = $(this).parents(".reactions-flex").find(".user-reaction")
        previous.removeClass("user-reaction")
        previous.find(".reaction-numbers").text(parseInt(previous.find(".reaction-numbers").text()) - 1)
        nb.text(nbInit + 1)
        $(this).addClass("user-reaction")
      }
      $.ajax({
        method: "GET",
        url: url,
      }).done(function (data) {
        if (data.state == "error") {
          alert(data.errorMessage)
          nb.text(nbInit)
        }
      })
    }
    return false
  })

  $("a.disabled").click(function () {
    return false
  })

  $(".launch-module").click(function () {
    
    return true;
  })
})
