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

function activity_navigation_add_element(id,value){
    // find associated template
    let template_container = document.getElementById(id+'_'+value+'_template_container') ;
    if (!template_container) {
        console.log(id+'_'+value+'_template_container : not found');
    } else {
        let clone = template_container.cloneNode(true);
        clone.removeAttribute("style");
        let now = new Date();
        let new_id = id+'_'+value+'_'+now.getTime();
        clone.setAttribute("id",new_id);
        // enable input
        let input = clone.getElementsByTagName('input');
        if (!input || input.length == 0){
            console.log(new_id+' : not input tag');
        } else {
            input[0].removeAttribute('disabled');
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
                    activity_navigation_add_element(id,condition);
                }
            });
        } 
    });
}
if (activityNavigationInit){
    activity_navigation_init(activityNavigationInit);
}