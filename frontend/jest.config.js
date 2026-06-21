module.exports = {
  testEnvironment: 'jsdom',
  moduleFileExtensions: ['js', 'json', 'vue'],
  transform: {
    '^.+\\.js$': 'babel-jest',
    '^.+\\.vue$': 'vue-jest'
  },
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
    '\\.(scss|css)$': 'identity-obj-proxy'
  },
  testMatch: ['<rootDir>/tests/**/*.spec.js'],
  setupFiles: ['<rootDir>/tests/setup.js'],
  transformIgnorePatterns: [
    '/node_modules/(?!(@vue|vue-router|vuex|axios|element-ui)/)'
  ]
}
