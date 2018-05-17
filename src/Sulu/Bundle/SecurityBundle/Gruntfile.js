module.exports = function(grunt) {
    var min = {},
        path = require('path'),
        srcpath = 'Resources/public/js',
        destpath = 'Resources/public/dist';

    // Build config "min" object dynamically.
    grunt.file.expand({cwd: srcpath}, '**/*.js').forEach(function(relpath) {
        // Create a target Using the verbose "target: {src: src, dest: dest}" format.
        min[relpath] = {
            src: path.join(srcpath, relpath),
            dest: path.join(destpath, relpath)
        };
        // The more compact "dest: src" format would work as well.
        // min[path.join(destpath, relpath)] = path.join(srcpath, relpath);
    });

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(function(name) {
        if ('grunt-cli' !== name) {
            grunt.loadNpmTasks(name);
        }
    });

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            hooks: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        src: [
                            'bin/hooks/*'
                        ],
                        dest: '.git/hooks/'
                    }
                ]
            }
        },

        exec: {
            hookrights: {
                command: 'chmod +x .git/hooks/pre-push'
            }
        },

        clean: {
            options: { force: true },
            hooks: ['.git/hooks/*']
        },
        watch: {
            options: {
                nospawn: true
            },
            compass: {
                files: ['Resources/public/scss/{,*/}*.{scss,sass}'],
                tasks: ['compass:dev']
            },
            scripts: {
                files: ['Resources/public/**'],
                tasks: ['publish']
            }
        },
        jshint: {
            options: {
                jshintrc: '.jshintrc'
            }
        },
        cssmin: {
            // TODO: options: { banner: '<%= meta.banner %>' },
            compress: {
                files: {
                    'dist/main.min.css': ['Resources/public/css/']
                }
            }
        },
        compass: {
            dev: {
                options: {
                    sassDir: 'Resources/public/scss/',
                    specify: ['Resources/public/scss/main.scss'],
                    cssDir: 'Resources/public/css/',
                    relativeAssets: false
                }
            }
        },
        uglify: min,
        replace: {
            build: {
                options: {
                    variables: {
                        'sulusecurity/js': 'sulusecurity/dist'
                    },
                    prefix: ''
                },
                files: [
                    {src: ['Resources/public/dist/main.js'], dest: 'Resources/public/dist/main.js'}
                ]
            }
        }
    });

    grunt.registerTask('build', [
        'uglify',
        'replace:build'
    ]);

    grunt.registerTask('default', [
        'watch'
    ]);

    grunt.registerTask('install:hooks', [
        'clean:hooks',
        'copy:hooks',
        'exec:hookrights'
    ]);
};
