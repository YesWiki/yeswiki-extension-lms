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

$(document).ready(function () {
  $("a.disabled").click(function () {
    return false
  })

  $(".launch-module").click(function () {
    return true;
  })
})

/** for menu index in small screen size **/
$('#burger').on('click', function(){
  $('#burger').toggleClass('burger-is-open');
})

/** == ==  Part for activity navigation edit == == */

function activity_navigation_select(elem,id){
  let value = elem.value ;
  if (value) {
    elem.parentNode.selectedIndex = "0"; // reset selection
    activity_navigation_add_element(id, value);
  }
}

function activity_navigation_add_element(id,value,conditionObject = {}){
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
      let inputs = clone.querySelectorAll('input,select');
      if (!inputs || inputs.length == 0){
          console.log(new_id+' : not input or select tag');
      } else {
          let end = inputs.length;
          for (let i=0;i<end;++i){
              inputs[i].removeAttribute('disabled');
              // set default value
              if (typeof conditionObject === 'object'){
                for (var key in conditionObject) {
                  if (inputs[i].name == id+'['+value+']['+key+']'){
                    inputs[i].value = conditionObject[key];
                  }
                }
              }
              // change name to be sure to be unique
              inputs[i].name += '['+activityNavigationConditionsEditUniqueId+']';
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
              clone.querySelector(".input-group.mb-3").appendChild(cloneButton);
              let container = document.getElementById(id+'_container');
              if (!container){
                  console.log(id+'_container : not found');
              } else {
                  container.appendChild(clone);

                  // add default params for scope
                  if (typeof conditionObject === 'object' && 'scope' in conditionObject){
                    for (var key in conditionObject.scope) {
                      let searchKey = '';
                      if (conditionObject.scope[key].course){
                        searchKey += conditionObject.scope[key].course ;
                      } else {
                        searchKey += '*';
                      }
                      searchKey += '/';
                      if (conditionObject.scope[key].module){
                        searchKey += conditionObject.scope[key].module ;
                      } else {
                        searchKey += '*';
                      }
                      let optionToActivate = clone.querySelector('.input-group.mb-3 select[name="'+id+'[scope_select]['
                          +activityNavigationConditionsEditUniqueId+']"] option[value="'+searchKey+'"]');
                      if(optionToActivate){
                        activity_navigation_scopeSelect(optionToActivate,id);
                      }
                    }
                  }
              }
          }
      }
  }
}

function activity_navigation_remove_condition(elem){
  let id = elem.parentNode.getAttribute('data-id');
  let conditionContainer = document.getElementById(id) ;
  if (!conditionContainer) {
      console.log(id+' : not found');
  } else {
      conditionContainer.remove();
  }
}

function activity_navigation_scopeSelect(elem,id){
  let value = elem.value ;
  let name = elem.parentNode.name;
  let base = elem.parentNode.parentNode.querySelector('.input-group-append.scope-list');
  if (value && name && base) {
    let longPrefix = id+'[scope_select]['
    let uniqueId = name.substr(longPrefix.length,name.length-(longPrefix.length+1)); // to remove $id[scope][...]
    // find other scope for this uniqueId
    let othersScope = base.querySelectorAll("input[name^='"+id+'[scope]['+uniqueId+']'+"']");
    let newIndex = othersScope.length ;
    // reset selection
    elem.parentNode.selectedIndex = "0";
    // find associated template
    let template_container = document.getElementById(id+'_scope_template_container') ;
    if (!template_container) {
        console.log(id+'_scope_template_container : not found');
    } else {
        let clone = template_container.cloneNode(true);
        clone.setAttribute("value",value);
        clone.removeAttribute("id");
        clone.removeAttribute("style");
        clone.removeAttribute('disabled');
        clone.name += '['+uniqueId+']['+newIndex+']';
        // clear current message
        let empty_message = base.querySelector('span.input-group-text i.empty-message');
        if (empty_message){
          empty_message.setAttribute("style","display:none !important;");
        }
        let baseText = base.querySelector('span.input-group-text');
        var baseList = baseText.querySelector('ul');
        if (!baseList){
          baseList = document.createElement('ul');
          baseText.appendChild(baseList);
        }
        let newMessage = document.createElement('li');
        newMessage.setAttribute("data-name",clone.name);
        newMessage.innerText = value ;
        // add remove button
        let removeBtn = document.createElement('i');
        removeBtn.classList.add('fas');
        removeBtn.classList.add('fa-times');
        removeBtn.setAttribute("onclick","activity_navigation_scope_remove(this)");
        newMessage.innerText += ' ';
        newMessage.appendChild(removeBtn);
        baseList.appendChild(newMessage);
        base.appendChild(clone);
    }
  }
}

function activity_navigation_scope_remove(elem){
  let name = elem.parentNode.getAttribute("data-name");
  // search associated input
  let baseUl = elem.parentNode.parentNode;
  let inputs = baseUl.parentNode.parentNode.querySelectorAll("input[name='"+name+"']");
  let end = inputs.length;
  for (let i=0;i<end;++i){
    inputs[i].remove() ;
  }
  // search associated li
  let lis = baseUl.querySelectorAll("li[data-name='"+name+"']");
  end = lis.length;
  for (let i=0;i<end;++i){
    lis[i].remove() ;
  }

  if(baseUl.children.length == 0){
    let empty_message = baseUl.parentNode.querySelector('i.empty-message');
    if (empty_message){
      empty_message.removeAttribute("style");
    }
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
          let message = 'Error when calling url: '+url;
          if (data.formattedMessages != undefined){
            message += ';'+data.formattedMessages ;
          }
          checkActivityNavigationConditionsError(elem,message);
      } else if (data.conditionsMet == undefined )  {
          checkActivityNavigationConditionsError(elem,'No conditionsMet in result calling url: '+url+', data:'+JSON.stringify(data));
      } else if (data.conditionsMet) {
          if (data.reactionsNeeded){
            // reaction needed block remove
            blockReactionRemove = true;
          }
          checkActivityNavigationConditionsRight(elem,data.url);
      } else if (!data.errorStatus) {
          checkActivityNavigationConditionsWrong(elem,data.formattedMessages);
      } else {
          checkActivityNavigationConditionsError(elem,'Error when calling url: '+url+';'+data.formattedMessages);
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