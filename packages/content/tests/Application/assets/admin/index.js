// This file should only be changed by the `bin/console sulu:admin:update-build` command:
// See https://docs.sulu.io/en/latest/upgrades/upgrade-2.x.html

// Sulu Core Bundles
import {startAdmin} from 'sulu-admin-bundle';
import 'sulu-audience-targeting-bundle';
import 'sulu-category-bundle';
import 'sulu-contact-bundle';
import 'sulu-custom-url-bundle';
import 'sulu-location-bundle';
import 'sulu-media-bundle';
import 'sulu-page-bundle';
import 'sulu-preview-bundle';
import 'sulu-route-bundle';
import 'sulu-search-bundle';
import 'sulu-security-bundle';
import 'sulu-snippet-bundle';
import 'sulu-trash-bundle';
import 'sulu-website-bundle';

// Add project specific javascript code and import of additional bundles to the following file:
import './app.js';

// Start admin application
startAdmin();
