function getInitParams(
  serviceUrl,
  partnerId,
  ks,
  entryId,
  uiConfId,
  userDisplayName
) {
  return {
    messageType: "kea-config",
    data: {
      /* URL of the Kaltura Server to use */
      service_url: serviceUrl,
      /* The Kaltura Account ID the entry we will edit belongs to */
      partner_id: partnerId,
      /* The ID the entry we will edit */
      entry_id: entryId,
      /* The Kaltura Session to use when editing this entry */
      ks: ks,
      /* The Kaltura Session to use when previewing the edited entry */
      preview_ks: ks,
      /* id of the player (Universal Studio v2 version player) uiconf to be used in the editor */
      player_uiconf_id: uiConfId,
      /* id of the player (Universal Studio v2 version player) uiconf to be used when previewing */
      preview_player_uiconf_id: uiConfId,
      /* Boolean; pass true if a KS must be appended to the thumbnails url for access control profile requirements */
      load_thumbnail_with_ks: true,
      /* Show a nicer user name (instead of user ID): */
      user_dispaly_name: userDisplayName,
      /* language - used by priority:
       * 1. Custom locale (locale_url)
       *    full url of a json file with translations
       * 2. Locale code (language_code, one of:
       *    de, en, es, fr, ja, nl, pt-br, ru, zh-Hans, zh-Hant
       * 3. English default locale (fallback). */
      language_code: "en",
      //'locale_url': 'URL_OF_CUSTOM_LANGUAGE_FILE', see public/locale_en.json as example
      /* URL to be used for "Go to User Manual" in the editor help component */
      help_link: "https://knowledge.kaltura.com/node/1912",
      /* the initial tab to load the editor app on (editor, quiz, advertisements or hotspots) */
      tab: "editor",
      /* tabs to show in navigation */
      tabs: {
        edit: {
          name: "edit",
          permissions: ["trim"],
          userPermissions: ["trim"],
          showOnlyExpandedView: true,
          //preActivateMessage: 'optional: message to show before activating the tab',
          //preSaveMessage: 'optional: message to show before trimming (Save)',
          //preSaveAsMessage: 'optional: message to show before clipping (Save As)',
          showSaveButton: true,
          showSaveAsButton: false,
        },
        /*quiz: {
          name: "quiz",
          permissions: ["quiz", "questions-v2", "questions-v3"],
          userPermissions: ["quiz"],
        },
        advertisements: {
          name: "advertisements",
          permissions: ["CUEPOINT_MANAGE", "FEATURE_DISABLE_KMC_KDP_ALERTS"],
          showSaveButton: false,
        },*/
        hotspots: {
          name: "hotspots",
          showSaveButton: true,
        },
      },
      /* URL of an additional css file to load */
      //'css_url': 'YOUR_ADDITIONAL_CSS_URL',
      /* URL to redirect to when the user wishes to leave the editor */
      //'exit_link': "URL TO NAVIGATE TO WHEN EXITING THE APP.",
      /* Should the editor hide the navigation bar (the sidebar holding the tabs icons)? */
      hide_navigation_bar: false,
      /* Should the editor hide the "go to media" button after quiz creation? */
      hide_quiz_goto_media_button: true,
      /* When creating a new quiz, should the editor skip "start" screen and create the new quiz entry automatically upon entering the tab? */
      skip_quiz_start_page: false,
    },
  };
}

// Initialize the kaltura editor app communication:
var initParamsListener = window.addEventListener("message", function (e) {
  // validate postMessage recieved -
  var postMessageData;
  try {
    postMessageData = e.data;
  } catch (ex) {
    return;
  }

  /* This is the initialization request for init params,
   * should return a message where messageType = kea-config along with the initialization params (see above) */
  if (postMessageData.messageType === "kea-bootstrap") {
    e.source.postMessage(keaInitParams, e.origin);
  }

  // attach to the external Save button click:
  /*saveBtn.addEventListener("click", function () {
    // execute the Save command:
    e.source.postMessage({ messageType: "kea-do-save" }, e.origin);
  });

  // attach to the external Save As button click:
  saveAsBtn.addEventListener("click", function () {
    // execute the Save As command:
    // i.e. Start the clipping process (will prompt user)
    // And pass optional reference id param to keep reference to the external hosting app entry id.
    //    useful for cases where the hosting app was closed while clipping.
    e.source.postMessage(
      {
        messageType: "kea-do-save-as",
        data: { referenceId: referenceId },
      },
      e.origin
    );
  });
*/
  /* The video editing tab was loaded (clip/trim functionality)
   * enable the save and save as buttons:
   */
  if (postMessageData.messageType === "kea-editor-tab-loaded") {
    //saveAsBtn.removeAttribute("disabled");
    //saveBtn.removeAttribute("disabled");
  }

  // the quiz tab was enabled - disable the external buttons:
  if (postMessageData.messageType === "kea-quiz-tab-loaded") {
    //saveAsBtn.setAttribute("disabled", true);
    //saveBtn.setAttribute("disabled", true);
  }

  // the ads or hotspos tabs were enabled, enable only external save button (no save as option):
  if (
    postMessageData.messageType === "kea-advertisements-tab-loaded" ||
    postMessageData.messageType === "kea-hotspots-tab-loaded"
  ) {
    //saveAsBtn.setAttribute("disabled", true);
    //saveBtn.removeAttribute("disabled");
  }

  /* received when a trim action was requested.
   * message.data = {entryId}
   * should return a message where message.messageType = kea-trim-message
   * and message.data is the (localized) text to show the user.
   */
  if (postMessageData.messageType === "kea-trimming-started") {
    e.source.postMessage(
      {
        messageType: "kea-trim-message",
        data:
          "You must approve the media replacement in order to be able to watch the trimmed media",
      },
      e.origin
    );
  }

  /* received when a trim action is complete.
   * message.data = {entryId}
   * can be used to clear app cache, for example.
   */
  if (postMessageData.messageType === "kea-trimming-done") {
    console.log(
      "processing of entry with id " + message.data.entryId + " is complete"
    );
  }

  /* received when a clip was created.
   * postMessageData.data: {
   *  originalEntryId,
   *  newEntryId,
   *  newEntryName
   * }
   * should return a message where message.messageType = kea-clip-message,
   * and message.data is the (localized) text to show the user.
   * */
  if (postMessageData.messageType === "kea-clip-created") {
    // send a message to editor app which will show up after clip has been created:
    var message =
      "Thank you for creating a new clip, the new entry ID is: " +
      postMessageData.data.newEntryId;
    e.source.postMessage(
      {
        messageType: "kea-clip-message",
        data: message,
      },
      e.origin
    );
  }

  /* request for a KS to pass to the preview player
   * message.data = entryId
   * may return {
   *   messageType: kea-preview-ks
   *   data: ks
   * }
   * don't include user name or add sview privilege, for example.
   * if not provided, the main KS will be used */
  if (postMessageData.messageType === "kea-get-preview-ks") {
    e.source.postMessage(
      {
        messageType: "kea-preview-ks",
        data: ks,
      },
      e.origin
    );
  }

  /* request for a KS. required for entitlements and access control when "switching" entries
      (currently when creating a new quiz only).
    * message.data = entryId
    * may return {
    *   messageType: kea-ks
    *   data: ks
    * }
    * if not provided, the main KS will be used */
  if (postMessageData.messageType === "kea-get-ks") {
    e.source.postMessage(
      {
        messageType: "kea-ks",
        data: ks,
      },
      e.origin
    );
  }

  /* request for user display name.
   * message.data = {userId}
   * the hosting app can get the userId from: postMessageData.data.userId and then return corresponding display name
   * should return a message {messageType:kea-display-name, data: display name}
   */
  if (postMessageData.messageType === "kea-get-display-name") {
    // in this sample we've simplified and used the config file to set the display name.
    var displayName = userDisplayName;
    e.source.postMessage(
      {
        messageType: "kea-display-name",
        data: displayName,
      },
      e.origin
    );
  }

  /*
   * Fired when saving quiz's settings.
   * message.data = {entryId}
   */
  if (postMessageData.messageType === "kea-quiz-updated") {
    // do whatever, you can invalidate cache, etc..
  }

  /*
   * Fired when creating a new quiz
   * message.data = {entryId}
   */
  if (postMessageData.messageType === "kea-quiz-created") {
    // do whatever, you can invalidate cache, etc..
  }

  /*
   * Fired when modifying advertisements (save not performed yet).
   * message.data = {entryId}
   */
  if (postMessageData.messageType === "kea-advertisements-modified") {
    // do whatever, you can invalidate cache, etc..
  }

  /*
   * Fired when saving advertisements
   * message.data = {entryId}
   */
  if (postMessageData.messageType === "kea-advertisements-saved") {
    // do whatever, you can invalidate cache, etc..
  }

  /* received when user clicks the "go to media" button after quiz was created/edited
   * message.data = entryId
   * host should navigate to a page displaying the relevant media */
  if (postMessageData.messageType === "kea-go-to-media") {
    console.log("Hosting app should redirect to: " + postMessageData.data);
  }
}); // END OF initParamsListener
