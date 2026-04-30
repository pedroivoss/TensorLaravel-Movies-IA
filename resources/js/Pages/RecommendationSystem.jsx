/**
 * Pages/RecommendationSystem.jsx
 *
 * Página principal do sistema de recomendação de filmes.
 *
 * ── Layout ────────────────────────────────────────────────────────────────────
 *
 *   ┌─────────────────────────────────────────────────────────┐
 *   │  🎬 Movie Recommendation AI  (Header)                   │
 *   ├─────────────────────────────────────────────────────────┤
 *   │  Selecione um usuário:                                  │
 *   │  [Ana  20a] [Bruno  33a] [Camila  25a] ...              │  ← UserCard grid
 *   ├─────────────────────────────────────────────────────────┤
 *   │ (após selecionar usuário)                               │
 *   │  ┌─────────────────────────┐  ┌────────────────────┐   │
 *   │  │ 🔍 Busca de Filmes      │  │ ⭐ Recomendações   │   │
 *   │  │ [pesquisar...]          │  │ para Ana Lima       │   │
 *   │  │ [Dune 2021 ★8.3]       │  │ [Inception ★8.8]   │   │
 *   │  │ [★★★] [Marcar]         │  │ [★★★] [Marcar]     │   │
 *   │  └─────────────────────────┘  └────────────────────┘   │
 *   └─────────────────────────────────────────────────────────┘
 *
 * ── Fluxo de dados ────────────────────────────────────────────────────────────
 *   1. Carrega usuários da API ao montar a página.
 *   2. Ao selecionar um usuário:
 *      - Busca detalhes (filmes já assistidos) para marcar avaliações existentes.
 *      - Carrega recomendações para o usuário.
 *   3. Na busca geral: debounce de 500ms para não chamar a API a cada keystroke.
 *   4. Ao marcar como assistido: POST /api/ratings e atualiza o estado local.
 *
 * ── Próxima etapa do curso (TF.js) ───────────────────────────────────────────
 *   Substituir getRecommendations() por um Web Worker que:
 *     1. Recebe os dados de treino (ratings do usuário) via postMessage().
 *     2. Treina um modelo tf.sequential() no browser.
 *     3. Retorna os scores de recomendação de volta via onmessage().
 *   O layout desta página não muda — apenas a fonte dos dados de recomendação.
 */

import { useCallback, useEffect, useRef, useState } from 'react';
import {
    Alert, Avatar, Badge, Col, Divider, Empty, Layout,
    Row, Select, Skeleton, Space, Spin, Tag, Typography, Input, App,
} from 'antd';
import {
    ExperimentOutlined,
    FireOutlined,
    PlayCircleOutlined,
    RobotOutlined,
    SearchOutlined,
    UserOutlined,
} from '@ant-design/icons';

import MovieCard from '@/Components/MovieCard';

import { getMovies, getRecommendations, getUser, getUsers, saveRating } from '@/services/api';

// Paleta de cores para avatares — rotação por ID do usuário
const AVATAR_COLORS = [
    '#1677ff', '#52c41a', '#fa8c16', '#eb2f96',
    '#722ed1', '#13c2c2', '#faad14', '#f5222d',
];

const { Header, Content } = Layout;
const { Title, Text, Paragraph } = Typography;
const { Search } = Input;

// Debounce: aguarda N ms após parar de digitar para chamar a API de busca
const SEARCH_DEBOUNCE_MS = 500;

export default function RecommendationSystem() {
    const { message } = App.useApp();

    // ── Estado global ────────────────────────────────────────────────────────
    const [users, setUsers]                         = useState([]);
    const [selectedUser, setSelectedUser]           = useState(null);
    const [userDetail, setUserDetail]               = useState(null);    // inclui rated_movie_ids
    const [recommendations, setRecommendations]     = useState([]);
    const [searchQuery, setSearchQuery]             = useState('');
    const [searchResults, setSearchResults]         = useState([]);
    const [searchPagination, setSearchPagination]   = useState({ page: 1, lastPage: 1, total: 0 });

    // ── Estados de loading ───────────────────────────────────────────────────
    const [loadingUsers, setLoadingUsers]               = useState(true);
    const [loadingDetail, setLoadingDetail]             = useState(false);
    const [loadingRecommendations, setLoadingRecommendations] = useState(false);
    const [loadingSearch, setLoadingSearch]             = useState(false);

    const searchDebounceRef = useRef(null);

    // ── Carga inicial: lista de usuários ─────────────────────────────────────
    useEffect(() => {
        getUsers()
            .then(setUsers)
            .catch(() => message.error('Erro ao carregar usuários.'))
            .finally(() => setLoadingUsers(false));
    }, []);

    // ── Busca de filmes com debounce ─────────────────────────────────────────
    useEffect(() => {
        clearTimeout(searchDebounceRef.current);
        searchDebounceRef.current = setTimeout(() => {
            setLoadingSearch(true);
            getMovies(searchQuery, 1)
                .then(data => {
                    setSearchResults(data.data);
                    setSearchPagination({
                        page: data.current_page,
                        lastPage: data.last_page,
                        total: data.total,
                    });
                })
                .catch(() => message.error('Erro na busca de filmes.'))
                .finally(() => setLoadingSearch(false));
        }, SEARCH_DEBOUNCE_MS);

        return () => clearTimeout(searchDebounceRef.current);
    }, [searchQuery]);

    // ── Ao selecionar um usuário (recebe o id vindo do Select) ───────────────
    const handleSelectUser = useCallback(async (userId) => {
        // Limpar seleção quando o Select é limpo (userId = undefined)
        if (!userId) {
            setSelectedUser(null);
            setUserDetail(null);
            setRecommendations([]);
            return;
        }

        const user = users.find(u => u.id === userId);
        if (!user) return;

        setSelectedUser(user);
        setUserDetail(null);
        setRecommendations([]);

        // Carrega detalhes (rated_movie_ids) e recomendações em paralelo
        setLoadingDetail(true);
        setLoadingRecommendations(true);

        const [detail, recoData] = await Promise.allSettled([
            getUser(user.id),
            getRecommendations(user.id),
        ]);

        if (detail.status === 'fulfilled')      setUserDetail(detail.value);
        if (recoData.status === 'fulfilled')    setRecommendations(recoData.value.recommendations ?? []);
        if (detail.status === 'rejected')       message.error('Erro ao carregar perfil do usuário.');
        if (recoData.status === 'rejected')     message.error('Erro ao carregar recomendações.');

        setLoadingDetail(false);
        setLoadingRecommendations(false);
    }, [users]);

    // ── Salvar avaliação ──────────────────────────────────────────────────────
    const handleRate = useCallback(async (movieId, rating) => {
        if (!selectedUser) {
            message.warning('Selecione um usuário antes de avaliar um filme.');
            return;
        }

        await saveRating({ user_id: selectedUser.id, movie_id: movieId, rating });

        // Atualiza o userDetail localmente (sem precisar recarregar da API)
        setUserDetail(prev => {
            if (!prev) return prev;
            const alreadyIn = prev.rated_movie_ids.includes(movieId);
            return {
                ...prev,
                ratings_count: alreadyIn ? prev.ratings_count : prev.ratings_count + 1,
                rated_movie_ids: alreadyIn
                    ? prev.rated_movie_ids
                    : [...prev.rated_movie_ids, movieId],
            };
        });

        // Atualiza o contador no card do usuário na grade
        setUsers(prev => prev.map(u =>
            u.id === selectedUser.id
                ? { ...u, ratings_count: (userDetail?.ratings_count ?? u.ratings_count) + 1 }
                : u
        ));

        message.success(`Avaliado com ${rating} ${rating === 1 ? 'estrela' : 'estrelas'}!`);
    }, [selectedUser, userDetail]);

    // ── Render helpers ────────────────────────────────────────────────────────

    /** IDs dos filmes já avaliados pelo usuário selecionado */
    const ratedIds = userDetail?.rated_movie_ids ?? [];

    /** Retorna a nota atual de um filme (null se não avaliado) */
    const currentRating = (movieId) => ratedIds.includes(movieId) ? 3 : null;
    // Nota: em uma versão com Rating mais completa, buscaria a nota real.
    // Por ora retornamos null (não avaliado) ou 3 (já avaliado, nota desconhecida).

    return (
        <Layout style={{ minHeight: '100vh', background: '#f5f6fa' }}>

            {/* ── Header ─────────────────────────────────────────────────── */}
            <Header style={{
                background: '#fff',
                borderBottom: '2px solid #1677ff',
                padding: '0 28px',
                display: 'flex',
                alignItems: 'center',
                gap: 10,
                position: 'sticky',
                top: 0,
                zIndex: 100,
                height: 56,
            }}>
                <PlayCircleOutlined style={{ fontSize: 24, color: '#1677ff' }} />
                <Title level={4} style={{ margin: 0, lineHeight: 1, fontSize: 18 }}>
                    Movie Recommendation AI
                </Title>
                <Text type="secondary" style={{ fontSize: 12, marginTop: 1 }}>
                    · Laravel + React
                </Text>
                <div style={{ marginLeft: 'auto' }}>
                    <Tag icon={<RobotOutlined />} color="blue">
                        TF.js — em breve
                    </Tag>
                </div>
            </Header>

            <Content style={{ padding: '24px', maxWidth: 1400, margin: '0 auto', width: '100%' }}>

                {/* ── Seção 1: Seletor de usuário ─────────────────────────── */}
                <div style={{
                    background: '#fff',
                    borderRadius: 12,
                    padding: 24,
                    marginBottom: 16,
                    boxShadow: '0 1px 4px rgba(0,0,0,0.06)',
                }}>
                    <Space align="center" style={{ marginBottom: 12 }}>
                        <UserOutlined style={{ fontSize: 20, color: '#1677ff' }} />
                        <Title level={4} style={{ margin: 0 }}>Selecione um Usuário</Title>
                        {!loadingUsers && (
                            <Tag color="blue">{users.length} usuários</Tag>
                        )}
                    </Space>

                    <Text type="secondary" style={{ display: 'block', marginBottom: 14 }}>
                        Escolha um usuário para carregar o perfil e ver as recomendações personalizadas.
                    </Text>

                    {/* Select com busca embutida — equivalente ao Select2 */}
                    <Select
                        showSearch
                        allowClear
                        placeholder="Digite o nome para buscar..."
                        loading={loadingUsers}
                        value={selectedUser?.id ?? null}
                        onChange={handleSelectUser}
                        style={{ width: '100%' }}
                        size="large"
                        filterOption={(input, option) =>
                            option.label.toLowerCase().includes(input.toLowerCase())
                        }
                        optionRender={(option) => {
                            const u = option.data;
                            // Ant Design pode chamar optionRender para estados internos sem dados (ex: clear)
                            if (!u?.name) return null;
                            const initials = u.name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
                            const color    = AVATAR_COLORS[u.id % AVATAR_COLORS.length];
                            return (
                                <Space align="center">
                                    <Avatar size={28} style={{ backgroundColor: color, fontSize: 11, fontWeight: 600, flexShrink: 0 }}>
                                        {initials}
                                    </Avatar>
                                    <span style={{ fontWeight: 500 }}>{u.name}</span>
                                    <Text type="secondary" style={{ fontSize: 12 }}>{u.age} anos</Text>
                                    {u.is_cold_start && (
                                        <Tag color="orange" style={{ fontSize: 10, padding: '0 4px', margin: 0 }}>Cold Start</Tag>
                                    )}
                                    {(u.favorite_genres ?? []).slice(0, 2).map(g => (
                                        <Tag key={g} style={{ fontSize: 10, padding: '0 4px', margin: 0 }}>{g}</Tag>
                                    ))}
                                </Space>
                            );
                        }}
                        options={users.map(u => ({
                            value: u.id,
                            label: u.name,   // usado pelo filterOption e pela busca
                            ...u,            // espalha os campos do usuário para o optionRender acessar via option.data
                        }))}
                    />
                </div>

                {/* ── Card de perfil do usuário selecionado ───────────────── */}
                {selectedUser && (() => {
                    const u       = selectedUser;
                    const initials = u.name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
                    const color    = AVATAR_COLORS[u.id % AVATAR_COLORS.length];
                    const count    = userDetail?.ratings_count ?? u.ratings_count;
                    return (
                        <div style={{
                            background: '#fff',
                            borderRadius: 12,
                            padding: '16px 24px',
                            marginBottom: 16,
                            boxShadow: '0 1px 4px rgba(0,0,0,0.06)',
                            borderLeft: `4px solid ${color}`,
                        }}>
                            <Space align="center" size={16} wrap>
                                <Avatar size={52} style={{ backgroundColor: color, fontSize: 18, fontWeight: 700 }}>
                                    {initials}
                                </Avatar>
                                <div>
                                    <Space align="center" size={8}>
                                        <Title level={5} style={{ margin: 0 }}>{u.name}</Title>
                                        <Text type="secondary">{u.age} anos</Text>
                                        {u.is_cold_start && (
                                            <Tag icon={<ExperimentOutlined />} color="orange">Cold Start</Tag>
                                        )}
                                        <Badge
                                            count={count}
                                            showZero
                                            color={count > 0 ? '#1677ff' : '#d9d9d9'}
                                            style={{ marginLeft: 4 }}
                                        />
                                        <Text type="secondary" style={{ fontSize: 12 }}>
                                            {count === 1 ? 'filme avaliado' : 'filmes avaliados'}
                                        </Text>
                                    </Space>
                                    <div style={{ marginTop: 8, display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                                        {(u.favorite_genres ?? []).length === 0 ? (
                                            <Text type="secondary" style={{ fontSize: 12 }}>Sem gêneros favoritos</Text>
                                        ) : (
                                            (u.favorite_genres ?? []).map((g, i) => (
                                                <Tag key={g} color={['blue','green','orange','purple','cyan','magenta','gold','volcano'][i % 8]}
                                                     style={{ margin: 0 }}>
                                                    {g}
                                                </Tag>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </Space>
                        </div>
                    );
                })()}

                {/* ── Seção 2: Busca + Recomendações (visível sempre) ───────── */}
                <Row gutter={[16, 16]}>

                    {/* ── Coluna esquerda: Busca Geral ─────────────────────── */}
                    <Col xs={24} lg={12}>
                        <div style={{
                            background: '#fff',
                            borderRadius: 12,
                            padding: 20,
                            boxShadow: '0 1px 4px rgba(0,0,0,0.06)',
                            height: '100%',
                        }}>
                            <Space align="center" style={{ marginBottom: 12 }}>
                                <SearchOutlined style={{ fontSize: 18, color: '#1677ff' }} />
                                <Title level={5} style={{ margin: 0 }}>Busca de Filmes</Title>
                                {searchPagination.total > 0 && (
                                    <Text type="secondary" style={{ fontSize: 12 }}>
                                        {searchPagination.total.toLocaleString()} filmes
                                    </Text>
                                )}
                            </Space>

                            {/* Campo de busca */}
                            <Search
                                placeholder="Digite o nome do filme..."
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                allowClear
                                style={{ marginBottom: 12 }}
                                prefix={<SearchOutlined />}
                            />

                            {/* Aviso: precisa de usuário para avaliar */}
                            {!selectedUser && (
                                <Alert
                                    type="info"
                                    showIcon
                                    message="Selecione um usuário acima para poder avaliar filmes."
                                    style={{ marginBottom: 12, fontSize: 12 }}
                                />
                            )}

                            {/* Lista de resultados */}
                            {loadingSearch ? (
                                <div style={{ textAlign: 'center', padding: 40 }}>
                                    <Spin />
                                    <div style={{ marginTop: 8, color: '#999', fontSize: 13 }}>Buscando...</div>
                                </div>
                            ) : searchResults.length === 0 ? (
                                <Empty
                                    description="Nenhum filme encontrado"
                                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                                />
                            ) : (
                                <div style={{ maxHeight: 520, overflowY: 'auto', paddingRight: 4 }}>
                                    {searchResults.map(movie => (
                                        <MovieCard
                                            key={movie.id}
                                            movie={movie}
                                            currentRating={currentRating(movie.id)}
                                            onRate={handleRate}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </Col>

                    {/* ── Coluna direita: Recomendações ───────────────────── */}
                    <Col xs={24} lg={12}>
                        <div style={{
                            background: '#fff',
                            borderRadius: 12,
                            padding: 20,
                            boxShadow: '0 1px 4px rgba(0,0,0,0.06)',
                            height: '100%',
                        }}>
                            <Space align="center" style={{ marginBottom: 12 }}>
                                <FireOutlined style={{ fontSize: 18, color: '#fa8c16' }} />
                                <Title level={5} style={{ margin: 0 }}>
                                    {selectedUser
                                        ? `Recomendações para ${selectedUser.name.split(' ')[0]}`
                                        : 'Recomendações'}
                                </Title>
                                {selectedUser && (
                                    <Tag icon={<ExperimentOutlined />} color="orange" style={{ fontSize: 11 }}>
                                        Placeholder — TF.js em breve
                                    </Tag>
                                )}
                            </Space>

                            {/* Estado: nenhum usuário selecionado */}
                            {!selectedUser ? (
                                <Empty
                                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                                    description={
                                        <span style={{ fontSize: 13 }}>
                                            Selecione um usuário para ver as recomendações personalizadas.
                                        </span>
                                    }
                                />
                            ) : loadingRecommendations ? (
                                <div style={{ textAlign: 'center', padding: 40 }}>
                                    <Spin />
                                    <div style={{ marginTop: 8, color: '#999', fontSize: 13 }}>Carregando recomendações...</div>
                                </div>
                            ) : (
                                <>
                                    {/* Banner informativo: algoritmo atual vs. futuro */}
                                    <Alert
                                        type="warning"
                                        showIcon
                                        icon={<RobotOutlined />}
                                        style={{ marginBottom: 12, fontSize: 12 }}
                                        message={
                                            selectedUser.is_cold_start
                                                ? 'Cold Start: sem gêneros favoritos — mostrando os filmes mais bem avaliados do IMDB.'
                                                : `Filtro por gêneros: ${(selectedUser.favorite_genres ?? []).slice(0, 3).join(', ')}${(selectedUser.favorite_genres?.length ?? 0) > 3 ? '...' : ''}. O TF.js substituirá isto com um modelo treinado no browser.`
                                        }
                                    />

                                    {/* Lista de recomendações */}
                                    {recommendations.length === 0 ? (
                                        <Empty
                                            description="Nenhuma recomendação disponível"
                                            image={Empty.PRESENTED_IMAGE_SIMPLE}
                                        />
                                    ) : (
                                        <div style={{ maxHeight: 520, overflowY: 'auto', paddingRight: 4 }}>
                                            {recommendations.map(movie => (
                                                <MovieCard
                                                    key={movie.id}
                                                    movie={movie}
                                                    currentRating={currentRating(movie.id)}
                                                    onRate={handleRate}
                                                    highlight // destaca borda azul — é uma recomendação
                                                />
                                            ))}
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </Col>
                </Row>

                {/* ── Rodapé informativo ───────────────────────────────────── */}
                <Divider style={{ marginTop: 32 }} />
                <div style={{ textAlign: 'center', paddingBottom: 16 }}>
                    <Text type="secondary" style={{ fontSize: 12 }}>
                        Próxima etapa: adaptar{' '}
                        <Text code style={{ fontSize: 11 }}>modelTrainingWorker.js</Text>
                        {' '}para treinar o modelo de recomendação diretamente no browser com TensorFlow.js.
                    </Text>
                </div>

            </Content>
        </Layout>
    );
}
