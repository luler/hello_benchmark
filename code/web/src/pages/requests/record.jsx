import React from "react";
import {Button, Input, Modal, Table, message, Divider, Popconfirm, Radio, Tag, Space} from "antd";
import {request_get, request_post} from "@/utils/request_tool";
import BaseComponent from "@/pages/BaseComponent";
import {getQueryString} from "@/utils/utils";

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
    this.setStateSimple('param.request_id', getQueryString('request_id'), () => {
      this.fetch()
    })
  }

  fetch(loading) {
    this.setState({loading: loading ?? true}, () => {
      request_get('/api/getRequestsRecordList', this.state.param).then((res) => {
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
      title: '压测对象名称',
      dataIndex: 'title',
      width: 200,
    },
    {
      title: '日志信息',
      dataIndex: 'content',
      width: 600,
      render: value => {
        return <div dangerouslySetInnerHTML={{__html: value}}/>
      }
    },
    {
      title: '请求总数',
      dataIndex: 'request_count',
    },
    {
      title: '失败总数',
      dataIndex: 'fail_count',
    },
    {
      title: '吞吐率',
      dataIndex: 'request_rate',
    },
    {
      title: '状态',
      dataIndex: 'status',
      render: value => {
        return [
          <Tag color='#2db7f5'>等待执行</Tag>,
          <Tag color='#108ee9'>正在执行中</Tag>,
          <Tag color='#87d068'>执行完成</Tag>,
          <Tag>已取消</Tag>
        ][value]
      }
    },
    {
      title: '创建时间',
      dataIndex: 'create_time',
    },
    {
      title: '操作',
      render: (record) => {
        return <div>
          <Button
            type='link'
            size='small'
            disabled={record.status !== 0}
            onClick={() => {
              Modal.confirm({
                title: '提示',
                content: '您确定要取消吗？',
                onOk: () => {

                  request_post('/api/cancelBenchmark', {ids: [record.id]}).then(res => {
                    if (res.code === 200) {
                      message.success(res.message)
                      this.fetch()
                    }
                  })

                }
              })
            }}
          >
            取消
          </Button>
          <Divider type='vertical'/>
          <Popconfirm
            title='您确定要删除吗？'
            onConfirm={() => {
              request_post('/api/delRequestsRecord', {ids: [record.id]}).then(res => {
                if (res.code === 200) {
                  message.success(res.message)
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
          margin: "20px 0",
          height: 70,
        }}
      >

        <Space size='middle'>
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

                  request_post('/api/delRequestsRecord', {ids: this.state.selectedRowKeys}).then(res => {
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
          <span>状态:</span>
          <Radio.Group
            defaultValue=''
            onChange={e => {
              this.setStateSimple('param.status', e.target.value, () => {
                this.fetch()
              })
            }}
          >
            <Radio value='' defaultChecked>全部</Radio>
            <Radio value='0'>等待执行</Radio>
            <Radio value='1'>正在执行中</Radio>
            <Radio value='2'>执行完成</Radio>
            <Radio value='3'>已取消</Radio>
          </Radio.Group>
        </Space>

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
      />
    </div>
  }

}
