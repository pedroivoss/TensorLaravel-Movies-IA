/**
 * Components/UserCard.jsx
 *
 * Card de usuário exibido na grade da tela principal.
 * Ao clicar, o usuário é "selecionado" e as seções de busca e
 * recomendações são carregadas abaixo.
 */
import { Avatar, Badge, Card, Tag, Tooltip, Typography } from 'antd';
import { UserOutlined, ExperimentOutlined } from '@ant-design/icons';

const { Text, Title } = Typography;

// Paleta de cores para os avatares — rotação por ID do usuário
const AVATAR_COLORS = [
    '#1677ff', '#52c41a', '#fa8c16', '#eb2f96',
    '#722ed1', '#13c2c2', '#faad14', '#f5222d',
];

// Paleta de cores para os tags de gênero
const GENRE_TAG_COLORS = [
    'blue', 'green', 'orange', 'purple',
    'cyan', 'magenta', 'gold', 'volcano',
];

/**
 * @param {{ user: object, selected: boolean, onClick: function }} props
 */
export default function UserCard({ user, selected, onClick }) {
    const avatarColor = AVATAR_COLORS[user.id % AVATAR_COLORS.length];

    // Iniciais do nome para o avatar (ex: "Ana Lima" → "AL")
    const initials = user.name
        .split(' ')
        .slice(0, 2)
        .map(word => word[0])
        .join('')
        .toUpperCase();

    return (
        <Card
            hoverable
            onClick={onClick}
            style={{
                cursor: 'pointer',
                borderColor: selected ? '#1677ff' : undefined,
                borderWidth: selected ? 2 : 1,
                boxShadow: selected
                    ? '0 0 0 2px rgba(22,119,255,0.2)'
                    : undefined,
                transition: 'all 0.2s ease',
                height: '100%',
            }}
            styles={{ body: { padding: '16px' } }}
        >
            {/* Linha superior: avatar + nome + badge Cold Start */}
            <div style={{ display: 'flex', alignItems: 'flex-start', gap: 12 }}>
                <Avatar
                    size={48}
                    style={{ backgroundColor: avatarColor, flexShrink: 0, fontSize: 16, fontWeight: 600 }}
                >
                    {initials}
                </Avatar>

                <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 6, flexWrap: 'wrap' }}>
                        <Title level={5} style={{ margin: 0, fontSize: 14, lineHeight: '20px' }} ellipsis>
                            {user.name}
                        </Title>

                        {/* Badge Cold Start: usuário sem gêneros e sem histórico */}
                        {user.is_cold_start && (
                            <Tooltip title="Sem gêneros e sem histórico — a IA usará tf.zeros()">
                                <Tag
                                    icon={<ExperimentOutlined />}
                                    color="orange"
                                    style={{ fontSize: 10, padding: '0 4px', lineHeight: '16px' }}
                                >
                                    Cold Start
                                </Tag>
                            </Tooltip>
                        )}
                    </div>

                    <Text type="secondary" style={{ fontSize: 12 }}>
                        {user.age} anos
                    </Text>
                </div>
            </div>

            {/* Gêneros favoritos como tags */}
            <div style={{ marginTop: 10, display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                {user.favorite_genres.length === 0 ? (
                    <Text type="secondary" style={{ fontSize: 11 }}>
                        Sem gêneros declarados
                    </Text>
                ) : (
                    user.favorite_genres.slice(0, 4).map((genre, i) => (
                        <Tag
                            key={genre}
                            color={GENRE_TAG_COLORS[i % GENRE_TAG_COLORS.length]}
                            style={{ fontSize: 11, margin: 0, padding: '0 6px' }}
                        >
                            {genre}
                        </Tag>
                    ))
                )}
                {/* "+N mais" quando há mais de 4 gêneros */}
                {user.favorite_genres.length > 4 && (
                    <Tooltip title={user.favorite_genres.slice(4).join(', ')}>
                        <Tag style={{ fontSize: 11, margin: 0, cursor: 'default' }}>
                            +{user.favorite_genres.length - 4}
                        </Tag>
                    </Tooltip>
                )}
            </div>

            {/* Rodapé: contagem de filmes assistidos */}
            <div style={{ marginTop: 10, borderTop: '1px solid #f0f0f0', paddingTop: 8 }}>
                <Badge
                    count={user.ratings_count}
                    showZero
                    color={user.ratings_count > 0 ? '#1677ff' : '#d9d9d9'}
                    style={{ marginRight: 6 }}
                />
                <Text type="secondary" style={{ fontSize: 11 }}>
                    {user.ratings_count === 1 ? 'filme avaliado' : 'filmes avaliados'}
                </Text>
            </div>
        </Card>
    );
}
