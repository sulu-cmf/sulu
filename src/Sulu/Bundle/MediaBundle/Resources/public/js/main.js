/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        sulumedia: '../../sulumedia/js',
        sulumediacss: '../../sulumedia/css',

        'extensions/masonry': '../../sulumedia/js/extensions/masonry',
        'extensions/sulu-buttons-mediabundle': '../../sulumedia/js/extensions/sulu-buttons',

        'services/sulumedia/media-router': '../../sulumedia/js/services/media-router',
        'services/sulumedia/overlay-manager': '../../sulumedia/js/services/overlay-manager',
        'services/sulumedia/collection-manager': '../../sulumedia/js/services/collection-manager',
        'services/sulumedia/image-editor': '../../sulumedia/js/services/image-editor',
        'services/sulumedia/media-manager': '../../sulumedia/js/services/media-manager',
        'services/sulumedia/format-manager': '../../sulumedia/js/services/format-manager',
        'services/sulumedia/user-settings-manager': '../../sulumedia/js/services/user-settings-manager',
        'services/sulumedia/file-icons': '../../sulumedia/js/services/file-icons',

        'type/media-selection': '../../sulumedia/js/validation/types/media-selection',
        'datagrid/decorators/masonry-view': '../../sulumedia/js/components/collections/masonry-decorator/masonry-view'
    }
});

define([
    'services/sulumedia/media-router',
    'services/sulumedia/user-settings-manager',
    'services/sulumedia/overlay-manager',
    'extensions/masonry',
    'extensions/sulu-buttons-mediabundle',
    'sulumedia/ckeditor/media-link',
    'css!sulumediacss/main'
], function(MediaRouter, UserSettingsManager, OverlayManager, MasonryExtension, MediaButtons, MediaLinkPlugin) {

    'use strict';

    return {
        name: "SuluMediaBundle",

        initialize: function(app) {
            var sandbox = app.sandbox;

            app.components.addSource('sulumedia', '/bundles/sulumedia/js/components');

            MasonryExtension.initialize(app);
            MediaButtons.initialize(app);

            sandbox.urlManager.setUrl('media', 'media/collections/edit:<%= collectionId %>/files/edit:<%= mediaId %>', function(data) {
                return {
                    mediaId: data.properties.media_id,
                    collectionId: data.properties.collection_id
                };
            });

            MediaRouter.initialize(app.sandbox.mvc.routes);

            sandbox.ckeditor.addPlugin(
                'mediaLink',
                new MediaLinkPlugin(app.sandboxes.create('plugin-media-link'))
            );
            sandbox.ckeditor.addToolbarButton('links', 'MediaLink', 'image');

            app.components.before('initialize', function() {
                if (this.name !== 'Sulu App') {
                    return;
                }

                this.sandbox.on('husky.dropzone.error', function(xhr, file) {
                    var title = this.sandbox.translate('sulu.dropzone.error.title'),
                        message = 'sulu.dropzone.error.message';

                    title = title.replace('{{filename}}', this.sandbox.util.cropMiddle(file.name, 20));

                    if(xhr.code === 5007){
                        message ='sulu.dropzone.error.message.wrong-filetype';
                    }

                    this.sandbox.emit('sulu.labels.error.show', message, title);
                }.bind(this));

                this.sandbox.on('husky.dropzone.error.file-to-big', function(message, file) {
                    var title = this.sandbox.translate('sulu.dropzone.error.file-to-big.title');
                    title = title.replace('{{filename}}', this.sandbox.util.cropMiddle(file.name, 20));

                    this.sandbox.emit('sulu.labels.error.show', message, title);
                }.bind(this));
            });
        }
    };
});
