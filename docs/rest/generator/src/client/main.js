import { connect } from 'react-redux';
import { App } from 'doca-integreat-theme';
import config from '../../config';

import 'doca-integreat-theme/styles/styles.css'

const mapStateToProps = state => ({
  schemas: state.schemas,
  config,
});

const Main = connect(
  mapStateToProps,
)(App);

export default Main;
