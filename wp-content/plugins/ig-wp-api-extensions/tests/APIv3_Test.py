#!/usr/bin/python3

import json, random, requests, sys

def get_posts(s, route, posts=False):
    if posts:
        return s.post('http://localhost/wordpress/augsburg/de/wp-json/extensions/v3/' + route, json=posts).text
    else:
        return s.get('http://localhost/wordpress/augsburg/de/wp-json/extensions/v3/' + route).text

def print_error(response):
    print('error ' + str(response['data']['status']) + ' ('+ response['code'] + '): ' + response['message'])

def main(args):
    s = requests.session()
    route = 'events'
    print('fetch all posts...')
    response = get_posts(s, route)
    try:
        posts = json.loads(response)
        posts_filtered = [{'id': posts[i]['id'], 'hash': posts[i]['hash']} for i in range(len(posts))]
    except ValueError:
        print('\t... could not load the following data:')
        print(response)
        sys.exit()
    except KeyError:
        print('\t... got the following error message:')
        print_error(json.loads(response))
        sys.exit()
    posts_ids = [{'id': posts[i]['id']} for i in range(len(posts))]
    print('\t... received ' + str(len(posts)) + ' posts.')
    print('simulate changes on the server by taking random sample of all posts...')
    if len(posts) > 1:
        local_posts = random.sample(posts, random.randint(1, len(posts) - 1))
    else:
        local_posts = []
    local_posts_filtered = [{'id': local_posts[i]['id'], 'hash': local_posts[i]['hash']} for i in range(len(local_posts))]
    local_posts_ids = [{'id': local_posts[i]['id']} for i in range(len(local_posts))]
    print('\t... got ' + str(len(local_posts)) + ' local posts.')
    print('modify local posts by adding non-existent id and malformed data...')
    local_posts_filtered.append({'hash': 12345, 'id': '9999', 'wrong attribute': 'irrelevant value'})
    print('\t... added: ', end='')
    print(local_posts_filtered[-1])
    print('fetch changed posts...')
    response = get_posts(s, route, local_posts_filtered)
    try:
        changed_posts = json.loads(response)['changed']
        changed_posts_filtered = [{'id': changed_posts[i]['id'], 'hash': changed_posts[i]['hash']} for i in range(len(changed_posts))]
        deleted_posts_ids = [post['id'] for post in json.loads(response)['deleted']]
        print('\t... received ' + str(len(changed_posts)) + ' changed posts and ' + str(len(deleted_posts_ids)) + ' deleted posts.')
        changed_posts_test = [post for post in posts_filtered if post not in local_posts_filtered]
        deleted_post_test = [post['id'] for post in local_posts_ids if post not in posts_ids]
        updated_posts = [post for post in local_posts_filtered + changed_posts_filtered if int(post['id']) not in deleted_posts_ids]
        if not sorted(updated_posts, key=lambda k: int(k['id'])) == sorted(posts_filtered, key=lambda k: int(k['id'])):
            test_success = False
        elif not deleted_post_test.sort() == deleted_posts_ids.sort():
            test_success = False
        elif not sorted(changed_posts_test, key=lambda k: int(k['id'])) == sorted(changed_posts_filtered, key=lambda k: int(k['id'])):
            test_success = False
        else:
            test_success = True
        if test_success:
            print('tests passed:')
            print('\tall posts ' + u"\u2216" + ' local posts = changed posts')
            print('\tlocal posts ' + u"\u2216" + ' all posts = deleted posts')
            print('\tlocal_posts ' + u"\u22C3" + '  changed_posts ' + u"\u22C2" + '  deleted_posts = all posts')
        else:
            print('test failed:')
            print('\t...the following local posts are missing:')
            print([post for post in posts_filtered if post not in updated_posts])
            print('\t...the following local posts should have been deleted:')
            print([post for post in updated_posts if post not in posts_filtered])
    except (ValueError):
        print('\t... could not load the following data:')
        print(response)
    except KeyError:
        print('\t... got the following error message:')
        print_error(response)

if __name__ == '__main__':
    main(sys.argv)
