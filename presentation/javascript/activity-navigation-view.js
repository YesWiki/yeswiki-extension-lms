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