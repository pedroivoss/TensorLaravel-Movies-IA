import { useCallback, useEffect, useRef, useState } from 'react';
import {
    Alert, App, Avatar, Badge, Button, Col, Divider, Empty,
    Input, Layout, Modal, Row, Select, Space, Spin, Tag, Typography,
} from 'antd';
import {
    ExperimentOutlined, FireOutlined, PlayCircleOutlined,
    RobotOutlined, SearchOutlined, UserOutlined,
} from '@ant-design/icons';

import MovieCard from '@/Components/MovieCard';
import { getMovies, getRecommendations, getUser, getUsers, saveRating } from '@/services/api';

const AVATAR_COLORS = ['#1677ff', '#52c41a', '#fa8c16', '#eb2f96', '#722ed1', '#13c2c2', '#faad14', '#f5222d'];
const LOG_COLOR = { log: '#c9d1d9', warn: '#e3b341', error: '#f85149', placeholder: '#30363d' };

const { Header, Content } = Layout;
const { Title, Text } = Typography;

export default function RecommendationSystem() {
    const { message } = App.useApp();

    // ── Core state ───────────────────────────────────────────────────────────
    const [users, setUsers]                   = useState([]);
    const [selectedUser, setSelectedUser]     = useState(null);
    const [userDetail, setUserDetail]         = useState(null);
    const [recommendations, setRecommendations] = useState([]);
    const [recoSource, setRecoSource]         = useState('api');   // 'api' | 'ai'
    const [recoMeta, setRecoMeta]             = useState(null);
    const [searchQuery, setSearchQuery]       = useState('');
    const [searchResults, setSearchResults]   = useState([]);
    const [searchTotal, setSearchTotal]       = useState(0);
    const [aiReady, setAiReady]               = useState(false);
    const [trainOpen, setTrainOpen]           = useState(false);
    const [training, setTraining]             = useState(false);
    const [trainDone, setTrainDone]           = useState(false);
    const [consoleLogs, setConsoleLogs]       = useState([
        { id: 0, level: 'placeholder', time: '—', text: 'Aguardando atividade do modelo...' },
    ]);

    // ── Loading flags ────────────────────────────────────────────────────────
    const [loadingUsers, setLoadingUsers]     = useState(true);
    const [loadingDetail, setLoadingDetail]   = useState(false);
    const [loadingReco, setLoadingReco]       = useState(false);
    const [loadingSearch, setLoadingSearch]   = useState(false);

    // ── Refs ─────────────────────────────────────────────────────────────────
    const debounceRef     = useRef(null);
    const consolePanelRef = useRef(null);
    const logIdRef        = useRef(1);
    const moviesRef       = useRef([]);
    const usersRef        = useRef([]);

    // ── Console interceptor ──────────────────────────────────────────────────
    useEffect(() => {
        const _log   = console.log.bind(console);
        const _warn  = console.warn.bind(console);
        const _error = console.error.bind(console);

        const push = (level, args) => {
            const time = new Date().toTimeString().slice(0, 8);
            const text = args.map(a => {
                if (a === null || a === undefined) return String(a);
                if (typeof a === 'object') { try { return JSON.stringify(a); } catch { return String(a); } }
                return String(a);
            }).join(' ');

            setConsoleLogs(prev => {
                const base = prev.filter(e => e.level !== 'placeholder');
                const next = [...base, { id: logIdRef.current++, level, time, text }];
                return next.length > 200 ? next.slice(-200) : next;
            });
        };

        console.log   = (...a) => { _log(...a);   push('log',   a); };
        console.warn  = (...a) => { _warn(...a);  push('warn',  a); };
        console.error = (...a) => { _error(...a); push('error', a); };

        return () => { console.log = _log; console.warn = _warn; console.error = _error; };
    }, []);

    // ── Auto-scroll console ──────────────────────────────────────────────────
    useEffect(() => {
        const p = consolePanelRef.current;
        if (p) p.scrollTop = p.scrollHeight;
    }, [consoleLogs]);

    // ── Initial load ─────────────────────────────────────────────────────────
    useEffect(() => {
        (async () => {
            try {
                const [usersData, moviesData] = await Promise.all([
                    getUsers(),
                    getMovies(''),
                ]);

                setUsers(usersData);
                usersRef.current  = usersData;
                moviesRef.current = moviesData.data ?? [];

                window.app = { movies: moviesRef.current, users: usersData };

                if (typeof window.loadModelFromDatabase === 'function') {
                    await window.loadModelFromDatabase(moviesRef.current, usersData);
                    setAiReady(!!window._model);
                }
            } catch {
                message.error('Erro ao carregar dados iniciais.');
            } finally {
                setLoadingUsers(false);
            }
        })();
    }, []);

    // ── Movie search ─────────────────────────────────────────────────────────
    useEffect(() => {
        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(async () => {
            setLoadingSearch(true);
            try {
                const data = await getMovies(searchQuery);
                setSearchResults(data.data ?? []);
                setSearchTotal(data.total ?? 0);
            } catch {
                message.error('Erro na busca de filmes.');
            } finally {
                setLoadingSearch(false);
            }
        }, 500);
        return () => clearTimeout(debounceRef.current);
    }, [searchQuery]);

    // ── Select user ──────────────────────────────────────────────────────────
    const handleSelectUser = useCallback(async (userId) => {
        if (!userId) {
            setSelectedUser(null); setUserDetail(null);
            setRecommendations([]); setRecoSource('api'); setRecoMeta(null);
            return;
        }

        const user = usersRef.current.find(u => u.id === userId);
        if (!user) return;

        setSelectedUser(user);
        setUserDetail(null);
        setRecommendations([]);
        setLoadingDetail(true);
        setLoadingReco(true);

        try {
            const detail = await getUser(userId);
            setUserDetail(detail);
        } catch {
            message.error('Erro ao carregar perfil.');
        } finally {
            setLoadingDetail(false);
        }

        try {
            const isAiReady = window._model && typeof window.getRecommendations === 'function';

            if (isAiReady) {
                console.log(`Gerando recomendações via IA para ${user.name}...`);
                const recs = await window.getRecommendations(userId);
                if (recs?.length > 0) {
                    setRecommendations(recs);
                    setRecoSource('ai');
                    setRecoMeta(null);
                    setLoadingReco(false);
                    return;
                }
            }

            console.log('IA não disponível. Usando API de recomendações...');
            const data = await getRecommendations(userId);
            setRecommendations(data.recommendations ?? []);
            setRecoSource('api');
            setRecoMeta(data);
        } catch {
            message.error('Erro ao carregar recomendações.');
        } finally {
            setLoadingReco(false);
        }
    }, []);

    // ── Train model ──────────────────────────────────────────────────────────
    const handleTrain = useCallback(async () => {
        if (typeof window.trainModel !== 'function') {
            message.error('TensorFlow.js ainda não carregado.');
            return;
        }

        setTraining(true);
        setTrainDone(false);
        window.app = { movies: moviesRef.current, users: usersRef.current };

        try {
            await window.trainModel();
            setAiReady(true);
            setTrainDone(true);
            message.success('Modelo treinado e salvo com sucesso!');

            if (selectedUser) {
                setLoadingReco(true);
                try {
                    const recs = await window.getRecommendations(selectedUser.id);
                    if (recs?.length > 0) {
                        setRecommendations(recs);
                        setRecoSource('ai');
                        setRecoMeta(null);
                    }
                } finally {
                    setLoadingReco(false);
                }
            }
        } catch (err) {
            console.error('Erro no treinamento:', err);
            message.error('Erro durante o treinamento.');
        } finally {
            setTraining(false);
        }
    }, [selectedUser]);

    // ── Rate movie ───────────────────────────────────────────────────────────
    const handleRate = useCallback(async (movieId, rating) => {
        if (!selectedUser) {
            message.warning('Selecione um usuário antes de avaliar.');
            return;
        }
        await saveRating({ user_id: selectedUser.id, movie_id: movieId, rating });
        setUserDetail(prev => {
            if (!prev) return prev;
            const already = prev.rated_movie_ids.includes(movieId);
            return {
                ...prev,
                ratings_count: already ? prev.ratings_count : prev.ratings_count + 1,
                rated_movie_ids: already ? prev.rated_movie_ids : [...prev.rated_movie_ids, movieId],
            };
        });
        message.success(`Avaliado com ${rating} ${rating === 1 ? 'estrela' : 'estrelas'}!`);
    }, [selectedUser]);

    // ── Helpers ──────────────────────────────────────────────────────────────
    const ratedIds      = userDetail?.rated_movie_ids ?? [];
    const currentRating = (id) => ratedIds.includes(id) ? 3 : null;
    const clearConsole  = () => setConsoleLogs([
        { id: logIdRef.current++, level: 'placeholder', time: '—', text: 'Console limpo.' }
    ]);

    // ── Render ───────────────────────────────────────────────────────────────
    return (
        <Layout style={{ minHeight: '100vh', background: '#f5f6fa' }}>

            {/* Header */}
            <Header style={{
                background: '#fff', borderBottom: '2px solid #1677ff',
                padding: '0 28px', display: 'flex', alignItems: 'center',
                gap: 10, position: 'sticky', top: 0, zIndex: 100, height: 56,
            }}>
                <PlayCircleOutlined style={{ fontSize: 24, color: '#1677ff' }} />
                <Title level={4} style={{ margin: 0, lineHeight: 1, fontSize: 18 }}>
                    Movie Recommendation AI
                </Title>
                <Text type="secondary" style={{ fontSize: 12 }}>· Laravel + React</Text>
                <div style={{ marginLeft: 'auto', display: 'flex', gap: 8, alignItems: 'center' }}>
                    {aiReady && <Tag icon={<RobotOutlined />} color="green">IA Carregada</Tag>}
                    <Button type="primary" icon={<RobotOutlined />} onClick={() => setTrainOpen(true)}>
                        Treinar Modelo
                    </Button>
                </div>
            </Header>

            <Content style={{ padding: 24, maxWidth: 1400, margin: '0 auto', width: '100%' }}>

                {/* Row 1: User selector + Console terminal */}
                <Row gutter={[12, 12]} style={{ marginBottom: 16 }}>

                    <Col xs={24} lg={8}>
                        <div style={{
                            background: '#fff', borderRadius: 12, padding: '16px 20px',
                            boxShadow: '0 1px 4px rgba(0,0,0,.06)', height: '100%',
                        }}>
                            <Space align="center" style={{ marginBottom: 12 }}>
                                <UserOutlined style={{ fontSize: 18, color: '#1677ff' }} />
                                <Title level={5} style={{ margin: 0 }}>Selecione um Usuário</Title>
                                {!loadingUsers && <Tag color="blue">{users.length} usuários</Tag>}
                            </Space>
                            <Select
                                showSearch allowClear
                                placeholder="Digite o nome para buscar..."
                                loading={loadingUsers}
                                value={selectedUser?.id ?? null}
                                onChange={handleSelectUser}
                                style={{ width: '100%' }}
                                filterOption={(input, opt) => opt.label.toLowerCase().includes(input.toLowerCase())}
                                optionRender={(opt) => {
                                    const u = opt.data;
                                    if (!u?.name) return null;
                                    const initials = u.name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
                                    const color = AVATAR_COLORS[u.id % AVATAR_COLORS.length];
                                    return (
                                        <Space align="center">
                                            <Avatar size={24} style={{ background: color, fontSize: 10, fontWeight: 600, flexShrink: 0 }}>{initials}</Avatar>
                                            <span style={{ fontWeight: 500 }}>{u.name}</span>
                                            <Text type="secondary" style={{ fontSize: 11 }}>{u.age}a</Text>
                                            {u.is_cold_start && <Tag color="orange" style={{ fontSize: 10, padding: '0 4px', margin: 0 }}>Cold</Tag>}
                                        </Space>
                                    );
                                }}
                                options={users.map(u => ({ value: u.id, label: u.name, ...u }))}
                            />
                        </div>
                    </Col>

                    <Col xs={24} lg={16}>
                        <div style={{ background: '#1e1e2e', borderRadius: 12, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,.06)', height: '100%' }}>
                            {/* Terminal title bar */}
                            <div style={{ background: '#2a2a3e', padding: '10px 16px', display: 'flex', alignItems: 'center', gap: 8, borderBottom: '1px solid #3a3a5a' }}>
                                <div style={{ display: 'flex', gap: 6, marginRight: 4 }}>
                                    {['#ff5f57', '#ffbd2e', '#28c840'].map(c => (
                                        <div key={c} style={{ width: 12, height: 12, borderRadius: '50%', background: c }} />
                                    ))}
                                </div>
                                <span style={{ color: '#6e7681', fontSize: 12, fontFamily: 'Consolas, monospace' }}>modelTrainingWorker.js</span>
                                <div style={{ marginLeft: 'auto', display: 'flex', gap: 6, alignItems: 'center' }}>
                                    <span style={{ background: '#161b22', border: '1px solid #30363d', borderRadius: 4, padding: '1px 8px', color: '#58a6ff', fontSize: 11 }}>IA Log</span>
                                    <button onClick={clearConsole} style={{ background: 'transparent', border: '1px solid #30363d', borderRadius: 4, color: '#6e7681', padding: '1px 10px', fontSize: 11, cursor: 'pointer', fontFamily: 'Consolas, monospace' }}>
                                        clear
                                    </button>
                                </div>
                            </div>
                            {/* Log area */}
                            <div ref={consolePanelRef} style={{ background: '#0d1117', padding: '10px 14px', height: 160, overflowY: 'auto', fontFamily: 'Consolas, "Courier New", monospace', fontSize: 12, lineHeight: 1.8 }}>
                                {consoleLogs.map(entry => (
                                    <div key={entry.id} style={{ display: 'flex', gap: 10, minWidth: 0 }}>
                                        <span style={{ color: '#484f58', flexShrink: 0, fontSize: 11, paddingTop: 2 }}>{entry.time}</span>
                                        <span style={{ color: LOG_COLOR[entry.level] ?? '#c9d1d9', wordBreak: 'break-all' }}>{entry.text}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Col>
                </Row>

                {/* User profile */}
                {selectedUser && (() => {
                    const u = selectedUser;
                    const initials = u.name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
                    const color = AVATAR_COLORS[u.id % AVATAR_COLORS.length];
                    const count = userDetail?.ratings_count ?? u.ratings_count;
                    return (
                        <div style={{ background: '#fff', borderRadius: 12, padding: '14px 20px', marginBottom: 16, boxShadow: '0 1px 4px rgba(0,0,0,.06)', borderLeft: `4px solid ${color}` }}>
                            <Space align="center" size={14} wrap>
                                <Avatar size={48} style={{ background: color, fontSize: 16, fontWeight: 700 }}>{initials}</Avatar>
                                <div>
                                    <Space align="center" size={8} wrap>
                                        <Title level={5} style={{ margin: 0 }}>{u.name}</Title>
                                        <Text type="secondary">{u.age} anos</Text>
                                        {u.is_cold_start && <Tag icon={<ExperimentOutlined />} color="orange">Cold Start</Tag>}
                                        <Badge count={count} showZero color={count > 0 ? '#1677ff' : '#d9d9d9'} />
                                        <Text type="secondary" style={{ fontSize: 12 }}>{count === 1 ? 'filme avaliado' : 'filmes avaliados'}</Text>
                                    </Space>
                                    <div style={{ marginTop: 6, display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                                        {(u.favorite_genres ?? []).length === 0
                                            ? <Text type="secondary" style={{ fontSize: 12 }}>Sem gêneros favoritos</Text>
                                            : (u.favorite_genres ?? []).map((g, i) => (
                                                <Tag key={g} color={['blue','green','orange','purple','cyan','magenta','gold','volcano'][i % 8]} style={{ margin: 0 }}>{g}</Tag>
                                            ))
                                        }
                                    </div>
                                </div>
                            </Space>
                        </div>
                    );
                })()}

                {/* Movies + Recommendations */}
                <Row gutter={[16, 16]}>
                    <Col xs={24} lg={12}>
                        <div style={{ background: '#fff', borderRadius: 12, padding: 20, boxShadow: '0 1px 4px rgba(0,0,0,.06)', height: '100%' }}>
                            <Space align="center" style={{ marginBottom: 12 }}>
                                <SearchOutlined style={{ fontSize: 18, color: '#1677ff' }} />
                                <Title level={5} style={{ margin: 0 }}>Busca de Filmes</Title>
                                {searchTotal > 0 && <Text type="secondary" style={{ fontSize: 12 }}>{searchTotal.toLocaleString('pt-BR')} filmes</Text>}
                            </Space>
                            <Input placeholder="Digite o nome do filme..." value={searchQuery} onChange={e => setSearchQuery(e.target.value)} allowClear prefix={<SearchOutlined />} style={{ marginBottom: 12 }} />
                            {!selectedUser && <Alert type="info" showIcon message="Selecione um usuário acima para poder avaliar filmes." style={{ marginBottom: 12, fontSize: 12 }} />}
                            {loadingSearch
                                ? <div style={{ textAlign: 'center', padding: 40 }}><Spin /><div style={{ marginTop: 8, color: '#999', fontSize: 13 }}>Buscando...</div></div>
                                : searchResults.length === 0
                                    ? <Empty description="Nenhum filme encontrado" image={Empty.PRESENTED_IMAGE_SIMPLE} />
                                    : <div style={{ maxHeight: 520, overflowY: 'auto', paddingRight: 4 }}>{searchResults.map(m => <MovieCard key={m.id} movie={m} currentRating={currentRating(m.id)} onRate={handleRate} />)}</div>
                            }
                        </div>
                    </Col>

                    <Col xs={24} lg={12}>
                        <div style={{ background: '#fff', borderRadius: 12, padding: 20, boxShadow: '0 1px 4px rgba(0,0,0,.06)', height: '100%' }}>
                            <Space align="center" style={{ marginBottom: 12 }}>
                                <FireOutlined style={{ fontSize: 18, color: '#fa8c16' }} />
                                <Title level={5} style={{ margin: 0 }}>
                                    {selectedUser
                                        ? `${recoSource === 'ai' ? 'Sugestões de IA' : 'Recomendações'} para ${selectedUser.name.split(' ')[0]}`
                                        : 'Recomendações'}
                                </Title>
                                {selectedUser && (
                                    <Tag icon={<RobotOutlined />} color={recoSource === 'ai' ? 'green' : 'orange'} style={{ fontSize: 11 }}>
                                        {recoSource === 'ai' ? 'Inteligência Artificial' : 'Filtro por Gêneros'}
                                    </Tag>
                                )}
                            </Space>
                            {!selectedUser
                                ? <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={<span style={{ fontSize: 13 }}>Selecione um usuário para ver as recomendações.</span>} />
                                : loadingReco
                                    ? <div style={{ textAlign: 'center', padding: 40 }}><Spin /><div style={{ marginTop: 8, color: '#999', fontSize: 13 }}>Carregando recomendações...</div></div>
                                    : (
                                        <>
                                            <Alert
                                                type={recoSource === 'ai' ? 'success' : 'warning'}
                                                showIcon icon={<RobotOutlined />}
                                                style={{ marginBottom: 12, fontSize: 12 }}
                                                message={recoSource === 'ai'
                                                    ? 'Baseado no modelo treinado localmente com seu histórico e preferências.'
                                                    : recoMeta?.is_cold_start
                                                        ? 'Cold Start: sem histórico — mostrando os mais bem avaliados do IMDB.'
                                                        : `Filtro por gêneros: ${(recoMeta?.favorite_genres ?? []).slice(0, 3).join(', ')}. Clique em "Treinar Modelo" para ativar a IA.`
                                                }
                                            />
                                            {recommendations.length === 0
                                                ? <Empty description="Nenhuma recomendação disponível" image={Empty.PRESENTED_IMAGE_SIMPLE} />
                                                : <div style={{ maxHeight: 520, overflowY: 'auto', paddingRight: 4 }}>{recommendations.map(m => <MovieCard key={m.id} movie={m} currentRating={currentRating(m.id)} onRate={handleRate} highlight />)}</div>
                                            }
                                        </>
                                    )
                            }
                        </div>
                    </Col>
                </Row>

                <Divider style={{ marginTop: 32 }} />
                <div style={{ textAlign: 'center', paddingBottom: 16 }}>
                    <Text type="secondary" style={{ fontSize: 12 }}>
                        Modelo treinado com <Text code style={{ fontSize: 11 }}>TensorFlow.js</Text> diretamente no browser —
                        use <strong>Treinar Modelo</strong> para iniciar o treinamento e ativar as recomendações por IA.
                    </Text>
                </div>
            </Content>

            {/* Training Modal */}
            <Modal
                title={<Space><RobotOutlined style={{ color: '#1677ff' }} /><span>Treinar Modelo TF.js</span><Tag icon={<RobotOutlined />} color="orange" style={{ fontSize: 11 }}>TensorFlow.js</Tag></Space>}
                open={trainOpen}
                onCancel={() => !training && setTrainOpen(false)}
                footer={[<Button key="close" onClick={() => setTrainOpen(false)} disabled={training}>Fechar</Button>]}
                width={560}
                destroyOnClose={false}
                forceRender
            >
                <Text type="secondary" style={{ display: 'block', marginBottom: 16, fontSize: 13 }}>
                    O modelo será treinado diretamente no browser. Acompanhe o log ao lado.
                </Text>
                <Button type="primary" block size="large" loading={training} disabled={trainDone && !training} onClick={handleTrain} style={{ marginBottom: 16 }}>
                    {trainDone ? '✓ Modelo Pronto' : training ? 'Treinando...' : 'Iniciar Treinamento'}
                </Button>
                <Text type="secondary" style={{ fontSize: 12, display: 'block', marginBottom: 6 }}>Gráficos de Performance</Text>
                {/* visor-container: DOM managed by TFVisorView.js — intentionally no React children */}
                <div id="visor-container" style={{ height: 320, overflowY: 'auto', background: '#fff', borderRadius: 6, border: '1px solid #d9d9d9', padding: 10 }} />
            </Modal>
        </Layout>
    );
}
