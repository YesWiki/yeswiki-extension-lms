typeUserAttrs = {
  ...typeUserAttrs,
  ...{
    reactions: {
      fieldlabel: {
        label: _t('LMS_REACTIONS_FIELD_ACTIVATE_LABEL'), 
        value: "",
        placeholder: _t('LMS_ACTIVATE_REACTIONS')
      },
      value: {
        label: _t('LMS_REACTIONS_FIELD_DEFAULT_ACTIVATION_LABEL'), 
        value: "oui"
      },
      labels: {
        label: _t('LMS_REACTIONS_FIELD_LABELS_LABEL'), 
        value: ""
      },
      images: {
        label: _t('LMS_REACTIONS_FIELD_IMAGES_LABEL'), 
        value: "",
        placeholder: _t('LMS_REACTIONS_FIELD_IMAGES_PLACEHOLDER')
      },
      ids: {
        label: _t('LMS_REACTIONS_FIELD_IDS_LABEL'), 
        value: ""
      },
      read: readConf,
      write: writeconf,
      semantic: semanticConf,
    },
  }
};

templates = {
  ...templates,
  ...{
    reactions: function (field) {
      return { 
        field: `<i class="far fa-thumbs-up"></i> ${field.fieldlabel || _t('LMS_ACTIVATE_REACTIONS')}` ,
        onRender() {
            templateHelper.defineLabelHintForGroup(field, 'fieldlabel', _t('LMS_REACTIONS_FIELD_ACTIVATE_HINT'))
            templateHelper.defineLabelHintForGroup(field, 'value', _t('LMS_REACTIONS_FIELD_VALUE_HINT'))
            templateHelper.defineLabelHintForGroup(field, 'ids', _t('LMS_REACTIONS_FIELD_IDS_HINT'))
            templateHelper.defineLabelHintForGroup(field, 'images', _t('LMS_REACTIONS_FIELD_IMAGES_HINT'))
            templateHelper.defineLabelHintForGroup(field, 'labels', _t('LMS_REACTIONS_FIELD_LABELS_HINT'))
        }
      };
    },
  }
};

yesWikiMapping = {
  ...yesWikiMapping,
  ...{
    reactions: {
      ...defaultMapping,
      ...{
        2: "ids",
        3: "labels",
        4: "images",
        6: "fieldlabel"
      }
    },
  }
};

fields.push({
    label: _t('LMS_REACTIONS_FIELD'),
    name: "reactions",
    attrs: { type: "reactions" },
    icon: '<i class="far fa-thumbs-up"></i>',
  });

  typeUserDisabledAttrs = {
    ...typeUserDisabledAttrs,
    ...{ reactions: ['label'] }
  }

