// module entries : move image to be just below title
$(".BAZ_cadre_fiche.id1202 .lms-module-content").each(function(){
  var title = $(this).find('.BAZ_fiche_titre');
  var image = $(this).find('[data-id=bf_image]');
  image.insertAfter(title);
});
// module modals : move image to be just below title
$(".BAZ_cadre_fiche:not(:has(.lms-container))").each(function() {
  var title = $(this).find('.BAZ_fiche_titre');
  var image = $(this).find('[data-id=bf_image]');
  $(this).prepend(image).prepend(title);
});

// reactions votes
$(document).ready(function () {
  $(".add-reaction").click(function () {
    var url = $(this).attr("href")
    var nb = $(this).find(".reaction-numbers")
    var nbInit = parseInt(nb.text())
    let doAjax = true;
    if (url !== "#") {
      if ($(this).hasClass("user-reaction")) {
        if(typeof blockReactionRemove !== 'undefined' && blockReactionRemove){
          if (blockReactionRemoveMessage) {
            if (typeof toastMessage == "function"){
              toastMessage(blockReactionRemoveMessage,3000,'alert alert-warning');
            } else {
              alert(blockReactionRemoveMessage);
            }
          }
          doAjax = false
        } else {
          nb.text(nbInit - 1)
          $(this).removeClass("user-reaction")
        }
      } else {
        var previous = $(this).parents(".reactions-flex").find(".user-reaction")
        previous.removeClass("user-reaction")
        previous.find(".reaction-numbers").text(parseInt(previous.find(".reaction-numbers").text()) - 1)
        nb.text(nbInit + 1)
        $(this).addClass("user-reaction")
      }
      if (doAjax){        
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

/** for menu index in small screen size **/
let burger = document.getElementById('burger');
$('#burger').on('click', function(){
  $('#burger').toggleClass('is-open');
})

/** == ==  Part for activity navigation edit == == */

function activity_navigation_add_button(elem){
  let id = elem.getAttribute('data-id');
  if (!id) {
      id = elem.id ;
  }
  let selectObject = document.getElementById(id+'_select');
  if (!selectObject) {
      console.log(id+'_select : not found');
  } else {
      let value = selectObject.value ;
      if (value) {
          activity_navigation_add_element(id, value);
      }
  }
}

function activity_navigation_add_element(id,value,conditionObject = null){
  // find associated template
  let template_container = document.getElementById(id+'_'+value+'_template_container') ;
  if (!template_container) {
      console.log(id+'_'+value+'_template_container : not found');
  } else {
      let clone = template_container.cloneNode(true);
      clone.removeAttribute("style");
      activityNavigationConditionsEditUniqueId = activityNavigationConditionsEditUniqueId +1;
      let new_id = id+'_'+value+'_'+activityNavigationConditionsEditUniqueId;
      clone.setAttribute("id",new_id);
      // enable input
      let input = clone.querySelectorAll('input');
      if (!input || input.length == 0){
          console.log(new_id+' : not input tag');
      } else {
          let end = input.length;
          for (let i=0;i<end;++i){
              input[i].removeAttribute('disabled');
              // set default value
              for (var key in conditionObject) {
                if (input[i].name == id+'['+value+']['+key+']'){
                  input[i].value = conditionObject[key];
                }
              }
              // change name to be sure to be unique
              input[i].name += '['+activityNavigationConditionsEditUniqueId+']';
          }

          // Add remove button
          let removeBtn = document.getElementById(id+'_remove_button_template') ;
          if (!template_container) {
              console.log(id+'_remove_button_template : not found');
          } else {
              let cloneButton = removeBtn.cloneNode(true);
              cloneButton.setAttribute("id",new_id+"_remove_button");
              cloneButton.removeAttribute("style");
              cloneButton.setAttribute("data-id",new_id);
              clone.appendChild(cloneButton);
              let container = document.getElementById(id+'_container');
              if (!container){
                  console.log(id+'_container : not found');
              } else {
                  container.appendChild(clone);
              }
          }
      }
  }
}

function activity_navigation_remove_condition(elem){
  let id = elem.getAttribute('data-id');
  let conditionContainer = document.getElementById(id) ;
  if (!conditionContainer) {
      console.log(id+' : not found');
  } else {
      conditionContainer.remove();
  }
}

function activity_navigation_init(listInit){

  listInit.forEach(function (item){
      var id = item.id;
      let value = item.value;
      if (value && Array.isArray(value)){
          value.forEach(function (conditionObject){
              let condition = conditionObject.condition ;
              if (condition){
                  activity_navigation_add_element(id,condition,conditionObject);
              }
          });
      } 
  });
}

var activityNavigationConditionsEditUniqueId = 0;
if (typeof activityNavigationInit !== 'undefined'){
  activity_navigation_init(activityNavigationInit);
}

/** == == == == */

/** == ==  Part for activity navigation view == == */

function checkActivityNavigationConditions(elem,courseTag, moduleTag, activityTag = ''){
  // add wait cursor
  elem.classList.add("wait-cursor");
  // check running
  if (!checkActivityNavigationConditionsRunning){
      checkActivityNavigationConditionsRun(elem,courseTag, moduleTag, activityTag);
  }
}

function checkActivityNavigationConditionsRun(elem,courseTag, moduleTag, activityTag){
  checkActivityNavigationConditionsRunning  = true;
  if (!checkActivityNavigationConditionsURL){
      checkActivityNavigationConditionsWrong(elem,"<div>Erreur en vérifiant les conditions de passage.</div>");
  }
  // ajax call
  let url = checkActivityNavigationConditionsURL + '' + courseTag + '/' + moduleTag + '/' + activityTag ;
  $.get(url,function (data,status){
      if (status != 'success'){
          checkActivityNavigationConditionsError(elem,'Error when calling url: '+url);
      } else if (data.status == undefined )  {
          checkActivityNavigationConditionsError(elem,'No status in result calling url: '+url+', data:'+JSON.stringify(data));
      } else if (data.status == 0) {
          checkActivityNavigationConditionsRight(elem,data.url);
      } else if (data.status == 2) {
          checkActivityNavigationConditionsWrong(elem,data.message);
      } else if (data.status == 3) {
          // reaction needed block remove
          blockReactionRemove = true;
          checkActivityNavigationConditionsRight(elem,data.url);
      } else {
          checkActivityNavigationConditionsError(elem,'Error when calling url: '+url+';'+data.message);
      }
  });
}

function checkActivityNavigationConditionsError(elem,message){
  //remove wait cursor
  elem.classList.remove("wait-cursor");
  console.log(message);
  let id = elem.getAttribute('data-id');
  if(id){
      let container = getContainer(id)
      // clean helper container
      cleanHelperContainer(container);
      // add error icon
      addErrorIconToHelperContainer(container,id)
      // add error text
      addErrorTextToHelperContainer(container,id)
      // display container
      container.removeAttribute("style");
  }
  checkActivityNavigationConditionsRunning  = false;
}


function checkActivityNavigationConditionsWrong(elem,message){
  //remove wait cursor
  elem.classList.remove("wait-cursor");
  let id = elem.getAttribute('data-id');
  if(id){
      let container = getContainer(id)
      // clean helper container
      cleanHelperContainer(container);
      // add error icon
      addErrorIconToHelperContainer(container,id)
      // add error message
      let messageDiv = document.createElement('div');
      messageDiv.innerHTML = message;
      messageDiv.setAttribute("style",'float:right;');
      container.appendChild(messageDiv);
      // display container
      container.removeAttribute("style");
  }
  checkActivityNavigationConditionsRunning  = false;
}

function checkActivityNavigationConditionsRight(elem,url){
  // set url
  elem.setAttribute("href",url);
  // remove class
  elem.classList.remove("wait-cursor","disabled");
  // remove events
  elem.removeAttribute("onmouseover");
  elem.addEventListener("click",function (event){
      event.stopPropagation();
      window.location.href = this.getAttribute("href");
  });
  
  let id = elem.getAttribute('data-id');
  if(id){
      let container = getContainer(id)
      // clean helper container
      cleanHelperContainer(container);
      // add success icon
      addSuccessIconToHelperContainer(container,id)
      // display container without border
      container.classList.add("no-border");
      container.removeAttribute("style");
      // hide container
      // container.setAttribute("style","display:none;");
  }
  checkActivityNavigationConditionsRunning  = false;
}

function getContainer(id){
  var container = document.getElementById(id+'_conditionLink_help_container') ;
  container.classList.remove("no-border");
  return container;
}

function cleanHelperContainer(container){
  if (typeof container != "undefined"){
      container.innerHTML = "";
  }
  return container;
}

function addErrorIconToHelperContainer(container,id){
  if (typeof container != "undefined"){
      let icon = document.getElementById(id+'_error_icon') ;
      if (typeof icon != "undefined"){
          let cloneIcon = icon.cloneNode(true);
          cloneIcon.removeAttribute("id");
          cloneIcon.removeAttribute("style");
          container.appendChild(cloneIcon);
      }
  }
}
function addSuccessIconToHelperContainer(container,id){
  if (typeof container != "undefined"){
      let icon = document.getElementById(id+'_success_icon') ;
      if (typeof icon != "undefined"){
          let cloneIcon = icon.cloneNode(true);
          cloneIcon.removeAttribute("id");
          cloneIcon.removeAttribute("style");
          container.appendChild(cloneIcon);
      }
  }
}

function addErrorTextToHelperContainer(container,id){
  if (typeof container != "undefined"){
      let textContainer = document.getElementById(id+'_error_message') ;
      if (typeof textContainer != "undefined"){
          let textNode = textContainer.cloneNode(true);
          textNode.removeAttribute("id");
          textNode.removeAttribute("style");
          textNode.setAttribute("style",'float:right;');
          container.appendChild(textNode);
      }
  }
}

var checkActivityNavigationConditionsRunning = false;
/** == == == == */