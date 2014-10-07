//
module.exports = function(grunt) {
  // Project configuration.
  grunt.initConfig({
    jsbeautifier: {
        files : [
          'gk-tabs.js'
        ],
        options : {
        }
    },
    cssbeautifier : {
      files : [
        '*.css',
        'styles/*.css'
      ]
    },
    jshint: {
      allFiles: [
        'gk-tabs.js'
      ],
      options: {
        jshintrc: '.jshintrc'
      }
    }
  });

  grunt.loadNpmTasks('grunt-jsbeautifier');
  grunt.loadNpmTasks('grunt-cssbeautifier');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.registerTask('default', ['jshint', 'cssbeautifier', 'jsbeautifier']);
};