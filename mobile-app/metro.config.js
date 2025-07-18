const { getDefaultConfig } = require('expo/metro-config');

const config = getDefaultConfig(__dirname);

// Konfiguration für React Native Web
config.resolver.platforms = ['ios', 'android', 'native', 'web'];

// Zusätzliche Dateiendungen
config.resolver.sourceExts.push('cjs');

// Web-spezifische Konfiguration
config.resolver.alias = {
  'react-native$': 'react-native-web',
  'react-native-web$': 'react-native-web',
};

// Resolver-Konfiguration für bessere Kompatibilität
config.resolver.resolverMainFields = ['react-native', 'browser', 'main'];

// Ignore node_modules für Web und C++ Runtime Issues
config.resolver.blockList = [
  /node_modules\/react-native\/Libraries\/ReactNative\/PaperUIManager\.js$/,
  /node_modules\/react-native\/Libraries\/Core\/MessageThread\.js$/,
  /.*\/node_modules\/react-native\/Libraries\/Core\/.*\.js$/,
];

module.exports = config;
