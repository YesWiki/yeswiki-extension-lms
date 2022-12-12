// reactions votes
$(document).ready(function () {
    // handler reaction click
    $('.link-reaction-refactor').click(function(event) {
      event.preventDefault();
      event.stopPropagation();
      const extractData = (item) => {
        const nb = $(item).find('.reaction-numbers')
        return {
            url : $(item).attr('href'),
            data : $(item).data(),
            nb : nb,
            nbInit : parseInt(nb.text())
        }
      }
      const {url,data,nb,nbInit} = extractData(this)
      const deleteUserReaction = async (url,data,nb,nbInit,link) =>{
        const p = new Promise((resolve,reject)=>{
            let currentReactionId = data.reactionid
            if ('oldId' in data && (data.oldId === true || data.oldId === "true")){
                currentReactionId = 'reactionField'
            }
            $.ajax({
                method: 'GET',
                url: `${url}/${currentReactionId}/${data.id}/${data.pagetag}/${data.username}/delete`,
                success() {
                  nb.text(nbInit - 1)
                  $(link).removeClass('user-reaction')
                  const nbReactionLeft = parseFloat(
                    $(link).parents('.reactions-container').find('.max-reaction').text()
                  )
                  $(link)
                    .parents('.reactions-container')
                    .find('.max-reaction')
                    .text(nbReactionLeft + 1)
                  resolve()
                },
                error(jqXHR, textStatus, errorThrown) {
                  reactionManagementHelper.renderAjaxError('REACTION_NOT_POSSIBLE_TO_DELETE_REACTION', jqXHR, textStatus, errorThrown)
                  reject()
                }
            })
        })
        return await p.then((...args)=>Promise.resolve(...args))
      }
      if (url !== '#') {
        if ($(this).hasClass('user-reaction')) {
          // on supprime la reaction
          if (typeof blockReactionRemove !== 'undefined' && blockReactionRemove) {
            if (blockReactionRemoveMessage) {
              if (typeof toastMessage == 'function') {
                toastMessage(
                  blockReactionRemoveMessage,
                  3000,
                  'alert alert-warning'
                )
              } else {
                alert(blockReactionRemoveMessage)
              }
            }
            return false
          }
          const link = $(this)
          deleteUserReaction(url,data,nb,nbInit,link)
          return false
        }
        // on ajoute la reaction si le max n'est pas dépassé
        const nbReactionLeft = parseFloat($(this).parents('.reactions-container').find('.max-reaction').text())
        if (url !== '#' && nbReactionLeft == 0 && typeof blockReactionRemove !== 'undefined' && blockReactionRemove === true){
            var previous = $(this).closest(".reactions-flex").find(".user-reaction").first()
            if (typeof previous === 'object' && 'length' in previous && previous.length > 0){
                const {url:previousUrl,data:previousData,nb:previousNb,nbInit:previousNbInit} = extractData(previous)
                if (previousUrl !== '#'){
                    deleteUserReaction(previousUrl,previousData,previousNb,previousNbInit,$(previous))
                        .then(()=>{
                            $(this).click()
                        })
                    return false
                }
            }
        }
        if (nbReactionLeft > 0) {
          const link = $(this)
          $.ajax({
            method: 'POST',
            url,
            data,
            success() {
              $(link)
                .find('.reaction-numbers')
                .text(nbReactionLeft - 1)
  
              nb.text(nbInit + 1)
              $(link).addClass('user-reaction')
              $(link)
                .parents('.reactions-container')
                .find('.max-reaction')
                .text(nbReactionLeft - 1)
            },
            error(jqXHR, textStatus, errorThrown) {
              reactionManagementHelper.renderAjaxError('REACTION_NOT_POSSIBLE_TO_ADD_REACTION', jqXHR, textStatus, errorThrown)
            }
          })
        } else {
          const message = 'Vous n\'avez plus de choix possibles, vous pouvez retirer un choix existant pour changer'
          if (typeof toastMessage == 'function') {
            toastMessage(
              message,
              3000,
              'alert alert-warning'
            )
          } else {
            alert(message)
          }
        }
        return false
      }
    })
})