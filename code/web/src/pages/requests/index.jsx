import React from "react";
import {Button, Form, Input, Modal, Table, message, Divider, Popconfirm, Radio, Select, Tag, Tabs, Space} from "antd";
import {request_get, request_post} from "@/utils/request_tool";
import BaseComponent from "@/pages/BaseComponent";
import {MinusCircleOutlined, PlusOutlined, SaveOutlined} from "@ant-design/icons";
import {getFullPath} from "@/utils/utils";

export default class database extends BaseComponent {
  state = {
    param: {
      page: 1,
      page_rows: 10,
    },
    list: [],
    total: 0,
    visible: false,
    temp_data: {},
    loading: false,
    interval: 0,
  }

  componentDidMount() {
    this.fetch()
  }

  fetch(loading) {
    this.setState({loading: loading ?? true}, () => {
      request_get('/api/getRequestsList', this.state.param).then((res) => {
        this.setStateSimple('list', res.info.list)
        this.setStateSimple('total', res.info.total)
        this.setStateSimple('loading', false)
        if (res.info.list.findIndex(function (one) {
          return one.status === 0 || one.status === 1
        }) === -1) {
          if (this.state.interval > 0) {
            clearInterval(this.state.interval);
            this.setStateSimple('interval', 0);
          }
        } else {
          if (this.state.interval === 0) {
            const that = this
            this.setStateSimple('interval', setInterval(function () {
              that.fetch(false)
            }, 3000))
          }
        }
      })
    })
  }

  submitParam(request_id, type, data) {
    this.setStateSimple('loading_sumit', true, () => {

      request_post('/api/saveParams', {
        request_id: request_id,
        type: type,
        data: data,
      }).then((res) => {
        if (res.code === 200) {
          message.success(res.message)
          this.fetch()
        }
        this.setStateSimple('loading_sumit', false)
      })

    })

  }

  columns = [
    {
      title: 'ID',
      dataIndex: 'id',
    },
    {
      title: '名称',
      width: 200,
      render: record => {
        return <a
          onClick={() => {
            this.setStateSimple('temp_data', record, () => {
              this.setStateSimple('visible', true)
            })
          }}
        >
          {record.title}
        </a>
      }
    },
    {
      title: '类型',
      dataIndex: 'type',
    },
    {
      title: 'ip/域名',
      dataIndex: 'host',
    },
    {
      title: '端口',
      dataIndex: 'port',
    },
    {
      title: '路径',
      dataIndex: 'path',
    },
    {
      title: '压测持续时间',
      render: (record) => {
        return <div><Tag>{record.driver}</Tag>{record.duration}</div>
      }
    },
    {
      title: '模拟用户数量',
      dataIndex: 'connect_count',
    },
    {
      title: '超时设置',
      dataIndex: 'timeout',
    },
    {
      title: '吞吐率',
      render: record => {
        return <a
          title='点击查看压测历史'
          onClick={() => {
            window.location.href = getFullPath('/requests/record?request_id=' + record.id)
          }}
        >
          {record.request_rate}
        </a>
      }
    },
    {
      title: '操作',
      width: 180,
      render: (record) => {
        return <div>
          <Button
            loading={record.status === 0 || record.status === 1}
            size='small'
            type='link'
            onClick={() => {
              Modal.confirm({
                title: '提示',
                content: '您确定要启动压测吗？',
                onOk: () => {

                  request_post('/api/startBenchmark', {ids: [record.id]}).then(res => {
                    if (res.code === 200) {
                      message.success(res.message)
                      this.fetch()
                    }
                  })

                }
              })

            }}
          >
            启动压测
          </Button>
          <Divider type='vertical'/>
          <Popconfirm
            title='您确定要删除吗？'
            onConfirm={() => {
              request_post('/api/delBenchmark', {ids: [record.id]}).then(res => {
                if (res.code === 200) {
                  message.success('删除成功')
                  this.fetch()
                }
              })
            }}
          >
            <a
              style={{color: 'red'}}
            >
              删除
            </a>
          </Popconfirm>
        </div>
      }
    },
  ]

  render() {
    return <div>
      <div
        style={{
          background: 'white',
          padding: 20,
          margin: "20px 0"
        }}
      >
        <Button
          type='danger'
          onClick={() => {
            if (!this.state.selectedRowKeys || this.state.selectedRowKeys.length === 0) {
              message.warning('请选择需要删除的项目')
              return
            }
            Modal.confirm({
              title: '提示',
              content: '您确定要删除选中项目吗？',
              onOk: () => {

                request_post('/api/delBenchmark', {ids: this.state.selectedRowKeys}).then(res => {
                  if (res.code === 200) {
                    message.success('删除成功')
                    this.fetch()
                  }
                })

              }
            })
          }}
        >
          删除选中
        </Button>
        &nbsp;
        &nbsp;
        <Button
          type='primary'
          onClick={() => {
            this.setStateSimple('visible', true)
            this.setStateSimple('temp_data', {
              type: 'GET',
              driver: 'co',
              connect_count: 500,
              timeout: 10,
              duration: 10,
              scheme: 'http',
              port: 80,
              path: '/',
            })
          }}
        >
          添加
        </Button>
        &nbsp;
        &nbsp;
        <Button
          type="primary"
          onClick={() => {

            if (!this.state.selectedRowKeys || this.state.selectedRowKeys.length === 0) {
              message.warning('请选择需要启动压测的项目')
              return
            }

            Modal.confirm({
              title: '提示',
              content: '您确定要启动选中的项目进行压测吗？',
              onOk: () => {

                request_post('/api/startBenchmark', {ids: this.state.selectedRowKeys}).then(res => {
                  if (res.code === 200) {
                    message.success(res.message)
                    this.fetch()
                  }
                })

              }
            })
          }}
        >
          批量启动压测
        </Button>

        <Input.Search
          style={{
            width: 500,
            float: 'right'
          }}
          allowClear
          placeholder='请输入搜索关键字'
          onSearch={value => {
            this.setState({
              param: {
                ...this.state.param,
                search: value,
                page: 1,
              }
            }, () => {
              this.fetch()
            })
          }}
        />
      </div>
      <Table
        onChange={(pagination) => {
          this.setState({
            param: {
              ...this.state.param,
              page: pagination.current,
              page_rows: pagination.pageSize,
            }
          }, () => {
            this.fetch()
          })
        }}
        pagination={{
          showSizeChanger: true,
          current: this.state.param.page,
          total: this.state.total,
          pageSize: this.state.param.page_rows,
          showTotal: (total) => {
            return <div>总共 {total} 条数据</div>
          }
        }}
        rowSelection={{
          selectedRowKeys: this.state.selectedRowKeys || [],
          onChange: selectedRowKeys => {
            this.setStateSimple('selectedRowKeys', selectedRowKeys)
          }
        }}
        loading={this.state.loading}
        rowKey='id'
        dataSource={this.state.list}
        columns={this.columns}
        expandable={{
          expandedRowRender: record => {
            return <Tabs defaultActiveKey="1">
              <Tabs.TabPane tab="请求参数" key="1">
                <Form autoComplete="off" onFinish={(values) => {

                  this.submitParam(record.id, 1, values.param)

                }}>
                  <Form.List name='param' initialValue={record.param}>
                    {(fields, {add, remove}) => (
                      <>
                        {fields.map(({key, name, ...restField}) => (
                          <Space key={key} style={{display: 'flex', marginBottom: 8}} align="baseline">
                            <Form.Item
                              {...restField}
                              name={[name, 'name']}
                              rules={[{required: true, message: '请求参数键名'}]}
                            >
                              <Input style={{width: 400}} placeholder="请求参数键名"/>
                            </Form.Item>
                            <Form.Item
                              {...restField}
                              name={[name, 'value']}
                              rules={[{required: true, message: '请求参数键值'}]}
                            >
                              <Input style={{width: 400}} placeholder="请求参数键值"/>
                            </Form.Item>
                            <MinusCircleOutlined onClick={() => remove(name)}/>
                          </Space>
                        ))}
                        <Form.Item>
                          <Button type="dashed" onClick={() => add()} block icon={<PlusOutlined/>}>
                            添加
                          </Button>
                        </Form.Item>
                      </>
                    )}
                  </Form.List>
                  <Form.Item>
                    <Button loading={this.state.loading_sumit || false} type="primary" htmlType="submit"
                            icon={<SaveOutlined/>} style={{width: '100%'}}>
                      提交
                    </Button>
                  </Form.Item>
                </Form>
              </Tabs.TabPane>
              <Tabs.TabPane tab="RAW参数" key="2">
                <Form autoComplete="off" onFinish={values => {

                  this.submitParam(record.id, 2, values.raw)

                }}>
                  <Form.Item
                    name='raw'
                    initialValue={record.raw}
                  >
                    <Input.TextArea
                      disabled={record.type === 'GET'}
                      placeholder='请输入'
                      allowClear
                      autoSize={{minRows: 5}}
                    />
                  </Form.Item>
                  <Button
                    disabled={record.type === 'GET'}
                    loading={this.state.loading_sumit || false}
                    type="primary"
                    htmlType="submit"
                    icon={<SaveOutlined/>}
                    style={{width: '100%'}}>
                    提交
                  </Button>
                </Form>

              </Tabs.TabPane>
              <Tabs.TabPane tab="请求头" key="3">
                <Form autoComplete="off" onFinish={(values) => {

                  this.submitParam(record.id, 3, values.header)

                }}>
                  <Form.List name='header' initialValue={record.header}>
                    {(fields, {add, remove}) => (
                      <>
                        {fields.map(({key, name, ...restField}) => (
                          <Space key={key} style={{display: 'flex', marginBottom: 8}} align="baseline">
                            <Form.Item
                              {...restField}
                              name={[name, 'name']}
                              rules={[{required: true, message: '请求头键名'}]}
                            >
                              <Input style={{width: 400}} placeholder="请求头键名"/>
                            </Form.Item>
                            <Form.Item
                              {...restField}
                              name={[name, 'value']}
                              rules={[{required: true, message: '请求头键值'}]}
                            >
                              <Input style={{width: 400}} placeholder="请求头键值"/>
                            </Form.Item>
                            <MinusCircleOutlined onClick={() => remove(name)}/>
                          </Space>
                        ))}
                        <Form.Item>
                          <Button type="dashed" onClick={() => add()} block icon={<PlusOutlined/>}>
                            添加
                          </Button>
                        </Form.Item>
                      </>
                    )}
                  </Form.List>
                  <Form.Item>
                    <Button loading={this.state.loading_sumit || false} type="primary" htmlType="submit"
                            icon={<SaveOutlined/>} style={{width: '100%'}}>
                      提交
                    </Button>
                  </Form.Item>
                </Form>
              </Tabs.TabPane>
            </Tabs>
          },
          rowExpandable: record => true,
        }}
      />
      <Modal
        width={700}
        title='压测对象信息'
        visible={this.state.visible}
        onCancel={() => {
          this.setStateSimple('visible', false)
          this.setStateSimple('temp_data', {})
        }}
        okButtonProps={{
          loading: this.state.loading_ok || false
        }}
        onOk={() => {
          this.setStateSimple('loading_ok', true, () => {
            request_post('/api/saveRequests', this.state.temp_data).then(res => {
              if (res.code === 200) {
                message.success(res.message)
                this.setStateSimple('visible', false)
                this.setStateSimple('temp_data', {})
                this.fetch()
              }
              this.setStateSimple('loading_ok', false)
            })
          })

        }}
      >
        <Form
          labelCol={{span: 4}}
          wrapperCol={{span: 20}}
          autoComplete='off'
        >
          <Form.Item label='名称' required>
            <Input placeholder='请输入' onChange={(e) => {
              this.setStateSimple('temp_data.title', e.target.value)
            }} value={this.state.temp_data.title || ''}/>
          </Form.Item>
          <Form.Item label='类型' required>
            <Radio.Group
              value={this.state.temp_data.type}
              onChange={(e) => {
                if (e.target.value === 'WEBSOCKET') {
                  this.setStateSimple('temp_data.driver', 'co')
                }
                this.setStateSimple('temp_data.type', e.target.value)
              }}
            >
              <Radio value='GET'>GET</Radio>
              <Radio value='POST'>POST</Radio>
              <Radio value='PATCH'>PATCH</Radio>
              <Radio value='PUT'>PUT</Radio>
              <Radio value='DELETE'>DELETE</Radio>
              <Radio value='WEBSOCKET'>WEBSOCKET</Radio>
            </Radio.Group>
          </Form.Item>
          {this.state.temp_data.type && this.state.temp_data.type !== 'WEBSOCKET' &&
            <Form.Item label='请求协议' required>
              <Radio.Group
                value={this.state.temp_data.scheme}
                onChange={(e) => {
                  this.setStateSimple('temp_data.scheme', e.target.value)
                }}
              >
                <Radio value='http'>http</Radio>
                <Radio value='https'>https</Radio>
              </Radio.Group>
            </Form.Item>
          }
          <Form.Item label='压测驱动' required>
            <Select
              value={this.state.temp_data.driver}
              onChange={value => {
                this.setStateSimple('temp_data.driver', value)
              }}
            >
              <Select.Option value='co'>swoole多进程&协程</Select.Option>
              <Select.Option value='wrk' disabled={this.state.temp_data.type === 'WEBSOCKET'}>wrk多线程压测工具</Select.Option>
            </Select>
          </Form.Item>
          <Form.Item label='压测持续时间' required>
            <Input type='number' placeholder='请输入' onChange={(e) => {
              this.setStateSimple('temp_data.duration', e.target.value)
            }} value={this.state.temp_data.duration || ''} addonAfter='秒'/>
          </Form.Item>
          <Form.Item label='ip/域名' required>
            <Input placeholder='请输入' onChange={(e) => {
              this.setStateSimple('temp_data.host', e.target.value)
            }} value={this.state.temp_data.host || ''}/>
          </Form.Item>
          <Form.Item label='端口' required>
            <Input type='number' placeholder='请输入' onChange={(e) => {
              this.setStateSimple('temp_data.port', e.target.value)
            }} value={this.state.temp_data.port || ''}/>
          </Form.Item>
          <Form.Item label='路径' required>
            <Input placeholder='请输入' onChange={(e) => {
              this.setStateSimple('temp_data.path', e.target.value)
            }} value={this.state.temp_data.path || ''}/>
          </Form.Item>
          <Form.Item label='模拟用户' required>
            <Input type='number' placeholder='请输入' onChange={(e) => {
              this.setStateSimple('temp_data.connect_count', e.target.value)
            }} value={this.state.temp_data.connect_count || ''} addonAfter='并发'/>
          </Form.Item>
          <Form.Item label='超时设置' tooltip='当一个请求的响应时间大于该值时，视为请求失败。按实际需求，可适当调整该参数。' required>
            <Input type='number' placeholder='请输入' onChange={(e) => {
              this.setStateSimple('temp_data.timeout', e.target.value)
            }} value={this.state.temp_data.timeout || ''} addonAfter='秒'/>
          </Form.Item>
        </Form>
      </Modal>
    </div>
  }

}
