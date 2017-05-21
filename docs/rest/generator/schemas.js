/* eslint global-require: 0 */

import { fromJS } from 'immutable';

export default fromJS([
    require('../schemata/pages.json'),
    require('../schemata/event_pages.json'),
    require('../schemata/locations.json'),
    require('../schemata/languages.json')
]);
