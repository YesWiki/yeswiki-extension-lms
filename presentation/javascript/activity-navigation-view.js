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
    checkActivityNavigationConditionsRunning  = false;
}

function checkActivityNavigationConditionsWrong(elem,message){
    //remove wait cursor
    elem.classList.remove("wait-cursor");
    console.log(message);
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
    checkActivityNavigationConditionsRunning  = false;
}

var checkActivityNavigationConditionsRunning = false;