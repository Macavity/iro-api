

module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        /*
         * JS Hint spellchecking
         * Documentation: https://github.com/gruntjs/grunt-contrib-jshint
         */
        jshint: {
            options: {

                force: true,

                // Enforcing
                curly: false,
                browser: true,
                eqeqeq: false,

                // Relaxing
                eqnull: true,
                scripturl: true,

                ignores: [
             ]
            },

            files: [
            ]
        },

        copy: {
            main: {
                files: [
                    // Twitter Bootstrap Sass
                    {
                        expand: true,
                        cwd: 'vendor/twbs/bootstrap-sass/vendor/assets/stylesheets/',
                        src: '**/*.scss',
                        dest: 'public/assets/scss/'
                    },
                    // Twitter Bootstrap JS
                    {
                        expand: true,
                        cwd: 'vendor/twbs/bootstrap/dist/js/',
                        src: 'bootstrap.min.js',
                        dest: 'public/assets/js/'
                    },
                    // jquery
                    {
                        expand: true,
                        cwd: 'vendor/components/jquery/',
                        src: 'jquery.min.js',
                        dest: 'public/assets/js/'
                    }
                ]
            }
        },

        /*
         * Sass Task
         * Documentation: https://github.com/gruntjs/grunt-contrib-sass
         */
        sass: {
            /*
             * Standard task during frontend development
             */
            dist: {
                options: {
                    style: "compressed"
                },
                files: {
                    'public/assets/css/main.min.css': 'public/assets/scss/main.scss'
                }
            },
            dev: {
                options: {
                    style: "expanded",
                    debugInfo: true
                },
                files: {
                    'public/assets/css/main.css': 'public/assets/scss/main.scss'
                }
            }
        },

        /*
         * Watches for changes in files and executes the tasks
         */
        watch: {
            css: {
                files: [
                    'public/assets/**/*.scss'
                ],
                tasks: ['sass:dev']
            }
        }
    });

    // Each of these should be installed via npm
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // Used during development
    grunt.registerTask('default', [
        'sass:dev'
    ]);

    grunt.event.on('watch', function(action, filepath) {
        grunt.log.writeln(filepath + ' has ' + action);
    });
};