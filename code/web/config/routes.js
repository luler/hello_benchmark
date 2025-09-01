export default [
  {
    path: '/user',
    layout: false,
    routes: [
      {
        path: '/user',
        routes: [
          {
            name: 'login',
            path: '/user/login',
            component: './user/Login',
          },
          {
            component: './404',
          },
        ],
      },
    ],
  },
  {
    path: '/',
    redirect: '/requests/index',
  },
  {
    path: '/requests',
    name: '压测对象',
    icon: 'DatabaseOutlined',
    routes: [
      {
        name: '压测对象列表',
        path: '/requests/index',
        component: './requests/index',
      },
      {
        name: '压测结果历史',
        path: '/requests/record',
        component: './requests/record',
        // hideInMenu: true,
      },
      {
        component: './404',
      },
    ],
  },
  {
    component: './404',
  },
];
